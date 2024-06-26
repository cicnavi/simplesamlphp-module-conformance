<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Nuclei;

use Cicnavi\SimpleFileCache\SimpleFileCache;
use DateInterval;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Factories\BulkTestStateFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\Nuclei\BulkTest\State;
use SimpleSAML\Module\conformance\SspBridge;
use Throwable;
use UnexpectedValueException;

class BulkTest
{
    /**
     * Interval after which the state will be considered stale.
     */
    public const STATE_STALE_THRESHOLD_INTERVAL = 'PT5M';

    protected const CACHE_NAME = 'conformance-test-runner-cache';
    protected const CACHE_KEY_STATE = 'state';

    protected int $id;
    protected DateInterval $stateStaleThresholdInterval;
    protected State $state;
    protected CacheInterface $cache;

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected Helpers $helpers,
        protected BulkTestStateFactory $bulkTestStateFactory,
        protected MetaDataStorageHandler $metaDataStorageHandler,
        protected TestRunner $nucleiTestRunner,
        protected LoggerInterface $logger,
        CacheInterface $cache = null,
    ) {
        $this->id = $this->helpers->random()->int();
        $this->stateStaleThresholdInterval = new DateInterval(self::STATE_STALE_THRESHOLD_INTERVAL);
        $this->state = $this->bulkTestStateFactory->build($this->id);
        $this->cache = $cache ?? $this->resolveCache();

        $this->registerInterruptHandler();
    }

    public function run(): State
    {
        try {
            $this->validatePreRunState();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Pre-run state validation failed. Clearing cached state and continuing. Error was %s',
                $exception->getMessage()
            );
            $this->logger->warning($message);
            $this->state->addStatusMessage($message);
            $this->clearCachedState();
        }

        try {
            $this->validateRunConditions();
        } catch (Throwable $exception) {
            $message = sprintf('Run conditions are not met, stopping. Reason was: %s', $exception->getMessage());
            $this->logger->info($message);
            $this->state->addStatusMessage($message);
            return $this->state;
        }

        $this->logger->debug('Run conditions validated.');

        $this->initializeCachedState();

        $serviceProviders = $this->metaDataStorageHandler->getList(SspBridge::KEY_SET_SP_REMOTE);

        // We have a clean state, we can start processing.
        while (
            $this->shouldRun() &&
            ($spEntityId = (string)key($serviceProviders))
        ) {
            next($serviceProviders);

            try {
                $this->updateCachedState($this->state);

                $this->state->addStatusMessage("Starting with tests for: $spEntityId");
                $token = $this->moduleConfiguration->getLocalTestRunnerToken();

                $this->nucleiTestRunner->run($token, $spEntityId);

                $this->state->incrementSuccessfulTestsProcessed();
                $successMessage = sprintf('Successfully processed test with for SP %s.', $spEntityId);
                $this->logger->debug($successMessage);
                $this->state->addStatusMessage($successMessage);
            } catch (Throwable $exception) {
                $message = sprintf('Error with test processing: %s', $exception->getMessage());
                $context = ['spEntityId' => $spEntityId];
                $this->logger->error($message, $context);
                $this->state->incrementFailedTestsProcessed();
                $this->state->addStatusMessage($message);
            }
        }

        $this->clearCachedState();
        $this->state->setEndedAt(new DateTimeImmutable());
        return $this->state;
    }

    /**
     */
    protected function shouldRun(): bool
    {
        // Enable this code to tick, which will enable it to catch CTRL-C signals and stop gracefully.
        declare(ticks=1) {
            if ($this->state->getTotalTestsProcessed() > (PHP_INT_MAX - 1)) {
                $message = 'Maximum number of processed tests reached.';
                $this->logger->debug($message);
                $this->state->addStatusMessage($message);
                return false;
            }

            try {
                $this->validateSelfState();
            } catch (Throwable $exception) {
                $message = sprintf(
                    'Test runner state is not valid. Message was: %s',
                    $exception->getMessage()
                );
                $this->logger->warning($message);
                $this->state->addStatusMessage($message);
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function initializeCachedState(): void
    {
        // Make sure that the state does not exist in the cache.
        try {
            if ($this->getCachedState() !== null) {
                throw new UnexpectedValueException('Test runner state already initialized.');
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error initializing test runner state. Error was: %s.', $exception->getMessage());
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }

        $startedAt = new DateTimeImmutable();
        $this->state->setStartedAt($startedAt);
        $this->updateCachedState($this->state, $startedAt);
    }

    /**
     * @throws Exception
     */
    protected function validatePreRunState(): void
    {
        $cachedState = $this->getCachedState();

        // Empty state means that no other test runner is active.
        if ($cachedState === null) {
            return;
        }

        if ($cachedState->getRunnerId() === $this->id) {
            $message = 'Test runner ID in cached state same as new ID.';
            $this->logger->error($message);
            throw new Exception($message);
        }

        if ($cachedState->isStale($this->stateStaleThresholdInterval)) {
            $message = 'Stale state encountered.';
            $this->logger->warning($message);
            throw new Exception($message);
        }
    }

    /**
     * @throws Exception
     * @psalm-suppress PossiblyUnusedMethod
     */
    protected function validateSelfState(): void
    {
        $cachedState = $this->getCachedState();

        // Validate state before start.
        if ($this->state->hasRunStarted() === false) {
            if ($cachedState !== null) {
                $message = 'Test run has not started, however cached state has already been initialized.';
                throw new Exception($message);
            }
        }

        // Validate state after start.
        if ($this->state->hasRunStarted() === true) {
            if ($cachedState === null) {
                $message = 'Test run has started, however cached state has not been initialized.';
                throw new Exception($message);
            }

            if ($cachedState->getRunnerId() !== $this->id) {
                $message = 'Test runner ID differs from the ID in the cached state.';
                throw new Exception($message);
            }

            if ($cachedState->isStale($this->stateStaleThresholdInterval)) {
                $message = 'Test runner cached state is stale, which means possible test runner process shutdown' .
                    ' without cached state clearing.';
                throw new Exception($message);
            }

            if ($cachedState->getIsGracefulInterruptInitiated()) {
                $message = 'Graceful test processing interrupt initiated.';
                throw new Exception($message);
            }
        }
    }

    protected function isAnotherRunnerActive(): bool
    {
        try {
            $cachedState = $this->getCachedState();

            if ($cachedState === null) {
                return false;
            }

            // There is cached state, which would indicate that a test runner is active. However, make sure that the
            // state is not stale (which indicates that the runner was shutdown without state clearing). If stale,
            // this means that the test runner is not active.
            if ($cachedState->isStale($this->stateStaleThresholdInterval)) {
                $this->logger->warning('Stale cache encountered. Assuming no test runner is active.');
                return false;
            }

            return $cachedState->getRunnerId() !== $this->id;
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error checking if another test runner is active. To play safe, we will assume true. ' .
                'Error was: %s',
                $exception->getMessage()
            );
            $this->logger->error($message);
            return true;
        }
    }

    /**
     * @throws Exception
     */
    protected function resolveCache(): SimpleFileCache
    {
        try {
            $this->logger->debug('Trying to initialize test runner cache using SSP datadir.');
            $cache = new SimpleFileCache(
                self::CACHE_NAME,
                $this->sspConfig->getPathValue('datadir')
            );
            $this->logger->debug('Successfully initialized cache using SSP datadir.');
            return $cache;
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error initializing test runner cache using datadir. Error was: %s',
                $exception->getMessage()
            );
            $this->logger->debug($message);
        }

        try {
            $this->logger->debug('Trying to initialize test runner cache using SSP tempdir.');
            $cache = new SimpleFileCache(
                self::CACHE_NAME,
                $this->sspConfig->getPathValue('tempdir')
            );
            $this->logger->debug('Successfully initialized test runner cache using SSP tempdir.');
            return $cache;
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error initializing test runner cache using tempdir. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->debug($message);
        }

        try {
            $this->logger->debug('Trying to initialize test runner cache using system tmp dir.');
            $cache = new SimpleFileCache(self::CACHE_NAME);
            $this->logger->debug('Successfully initialized cache using system tmp dir.');
            return $cache;
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error initializing test runner cache. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->debug($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function clearCachedState(): void
    {
        /** @psalm-suppress InvalidCatch */
        try {
            $this->cache->delete(self::CACHE_KEY_STATE);
        } catch (Throwable | \Psr\SimpleCache\InvalidArgumentException $exception) {
            $message = sprintf(
                'Error clearing test runner cache. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function getCachedState(): ?State
    {
        /** @psalm-suppress InvalidCatch */
        try {
            /** @var ?State $state */
            $state = $this->cache->get(self::CACHE_KEY_STATE);
            if ($state instanceof State) {
                return $state;
            } else {
                return null;
            }
        } catch (Throwable | \Psr\SimpleCache\InvalidArgumentException $exception) {
            $message = sprintf('Error getting test runner state from cache. Error was: %s', $exception->getMessage());
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function updateCachedState(State $state, DateTimeImmutable $updatedAt = null): void
    {
        $updatedAt = $updatedAt ?? new DateTimeImmutable();
        $state->setUpdatedAt($updatedAt);

        /** @psalm-suppress InvalidCatch */
        try {
            $this->cache->set(self::CACHE_KEY_STATE, $state);
        } catch (Throwable | \Psr\SimpleCache\InvalidArgumentException $exception) {
            $message = sprintf('Error setting test runner state. Error was: %s.', $exception->getMessage());
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function validateRunConditions(): void
    {
        if ($this->isAnotherRunnerActive()) {
            $message = 'Another test runner is active.';
            $this->logger->debug($message);
            throw new Exception($message);
        }
    }

    protected function isCli(): bool
    {
        return $this->helpers->environment()->isCli();
    }

    /**
     * Register interrupt handler. This makes it possible to gracefully stop processing by
     * clearing the current state. It relies on pcntl extension, so to use this feature,
     * that extension has to be enabled.
     * @see https://www.php.net/manual/en/pcntl.installation.php
     * @return void
     */
    protected function registerInterruptHandler(): void
    {
        // pcntl won't be available in web server environment, so skip immediately.
        if (! $this->isCli()) {
            return;
        }

        // Extension pcntl doesn't come with PHP by default, so check if the proper function is available.
        if (! function_exists('pcntl_signal')) {
            $message = 'pcntl related functions not available, skipping registering interrupt handler.';
            $this->logger->info($message);
            $this->state->addStatusMessage($message);
            return;
        }

        /** @noinspection PhpComposerExtensionStubsInspection Module is still usable without this.*/
        pcntl_signal(SIGINT, [$this, 'handleInterrupt']);
        /** @noinspection PhpComposerExtensionStubsInspection Module is still usable without this.*/
        pcntl_signal(SIGTERM, [$this, 'handleInterrupt']);
    }

    /**
     * @throws Exception
     */
    protected function handleInterrupt(int $signal): void
    {
        $message = sprintf('Gracefully stopping processing. Interrupt signal was %s.', $signal);
        $this->state->addStatusMessage($message);
        $this->logger->info($message);
        $this->state->setIsGracefulInterruptInitiated(true);
        $this->updateCachedState($this->state);
    }
}
