<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Auth\Process;

use Psr\SimpleCache\InvalidArgumentException;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Cache;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Helpers\StateHelper;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\SspBridge;
use Throwable;

class Conformance extends ProcessingFilter
{
    final public const KEY_TEST_ID = 'testId';
    final public const KEY_RESPONDER = 'Responder';
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
     */
    public function __construct(
        array $config,
        mixed $reserved,
        Cache $cache = null,
        protected readonly ResponderResolver $responderResolver = new ResponderResolver(),
        protected readonly StateHelper $stateHelper = new StateHelper(),
        protected readonly SspBridge $sspBridge = new SspBridge(),
        Configuration $sspConfig = null,
        protected readonly ModuleConfiguration $moduleConfiguration = new ModuleConfiguration(),

    ) {
        parent::__construct($config, $reserved);

        $sspConfig ??= Configuration::getInstance();
        $this->cache = $cache ?? new Cache($sspConfig, $moduleConfiguration);
    }


    /**
     * Apply filter.
     *
     * @param array &$state The current request
     * @throws ConformanceException
     */
    public function process(array &$state): void
    {
        $spEntityId = $this->stateHelper->resolveSpEntityId($state);

        $testId = $this->resolveTestId($spEntityId);

        // If the test has not been pre-set, redirect to a page on which particular test can be chosen.
        if (is_null($testId)) {
            // Save state and redirect
            $id = $this->sspBridge->auth()->state()->saveState($state, self::KEY_STATE_STAGE_ID_TEST_SETUP);
            $url = $this->sspBridge->module()->getModuleURL('conformance/test/setup');

            $this->sspBridge->utils()->http()->redirectTrustedURL(
                $url,
                [self::KEY_STATE_ID => $id, self::KEY_SP_ENTITY_ID => $spEntityId]
            );
            return;
        }

        $responderCallable = $this->responderResolver->fromTestId($testId);
        if (is_null($responderCallable)) {
            throw new ConformanceException('No test responder available for test ID ' . $testId);
        }
        // TODO mivanci Check if responder already exists (it should, otherwise, the authproc is not set in IdP).
        $state[Conformance::KEY_RESPONDER] = $responderCallable;
    }

    /**
     * @throws ConformanceException
     */
    protected function resolveTestId(string $spEntityId): ?string
    {
        try {
            $testId = $this->cache->getTestId($spEntityId);
        } catch (Throwable | InvalidArgumentException $exception) {
            throw new ConformanceException('Error getting test ID from cache: ' . $exception->getMessage());
        }

        if (is_null($testId)) {
            return null;
        }

        return $testId;
    }
}
