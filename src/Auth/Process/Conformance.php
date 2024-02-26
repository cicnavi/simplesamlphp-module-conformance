<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Auth\Process;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Compat\Logger;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Cache;
use SimpleSAML\Module\conformance\Errors\CacheException;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Helpers\State;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\SspBridge;
use Throwable;

class Conformance extends ProcessingFilter
{
    final public const KEY_TEST_ID = 'testId';
    final public const KEY_STATE_STAGE_ID_TEST_SETUP = ModuleConfiguration::MODULE_NAME . '-test-setup';
    final public const KEY_STATE_ID = 'StateId';
    final public const KEY_SP_ENTITY_ID = 'spEntityId';

    protected readonly Cache $cache;

    /**
     * Initialize this filter.
     * Validate configuration parameters.
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     * @throws CacheException
     */
    public function __construct(
        array $config,
        mixed $reserved,
        Cache $cache = null,
        protected readonly ResponderResolver $responderResolver = new ResponderResolver(),
        protected readonly State $stateHelper = new State(),
        protected readonly SspBridge $sspBridge = new SspBridge(),
        Configuration $sspConfig = null,
        protected readonly LoggerInterface $logger = new Logger(),
    ) {
        parent::__construct($config, $reserved);

        try {
            $sspConfig ??= Configuration::getInstance();
        } catch (Exception $exception) {
            throw new CacheException(
                'Unable to initialize SimpleSAMLphp configuration.',
                (int)$exception->getCode(),
                $exception
            );
        }
        $this->cache = $cache ?? new Cache($sspConfig);
    }


    /**
     * Apply filter.
     *
     * @param array &$state The current request
     * @throws ConformanceException
     */
    public function process(array &$state): void
    {
        $this->logger->debug('Started Conformance authentication processing filter.');
        $spEntityId = $this->stateHelper->resolveSpEntityId($state);
        $this->logger->info("Resolved SP Entity ID: $spEntityId");

        $testId = $this->resolveTestId($spEntityId);

        // If the test has not been pre-set, redirect to a page on which particular test can be chosen.
        if (is_null($testId)) {
            $this->logger->info("No test ID has been set, redirecting to test setup page.");
            // Save state and redirect
            $id = $this->sspBridge->auth()->state()->saveState($state, self::KEY_STATE_STAGE_ID_TEST_SETUP);
            $url = $this->sspBridge->module()->getModuleURL('conformance/test/setup');

            $this->sspBridge->utils()->http()->redirectTrustedURL(
                $url,
                [self::KEY_STATE_ID => $id, self::KEY_SP_ENTITY_ID => $spEntityId]
            );
            return;
        }

        $this->logger->info("Resolved test ID: $testId");

        $responderCallable = $this->responderResolver->fromTestIdOrThrow($testId);
        $this->logger->info('Resolved responder callable.', [var_export($responderCallable, true)]);

        $this->stateHelper->setResponder($state, $responderCallable);
        $this->logger->debug('New responder callable set in state.');
    }

    /**
     * @throws ConformanceException
     */
    protected function resolveTestId(string $spEntityId): ?string
    {
        try {
            $testId = $this->cache->getTestId($spEntityId);
        } catch (Throwable $exception) {
            throw new ConformanceException('Error getting test ID from cache: ' . $exception->getMessage());
        }

        if (is_null($testId)) {
            return null;
        }

        return $testId;
    }
}
