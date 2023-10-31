<?php

namespace SimpleSAML\Module\conformance\Auth\Process;

use Cicnavi\SimpleFileCache\Exceptions\CacheException;
use SimpleSAML\Auth;
use SimpleSAML\Module;
use SimpleSAML\Module\conformance\Cache;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\ModuleConfig;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Module\conformance\Helpers\StateHelper;

/**
 * TODO mivanci move to SSP v2.1
 */
class Conformance extends Auth\ProcessingFilter
{
	public const KEY_TEST_ID = 'testId';
	public const KEY_RESPONDER = 'Responder';
	public const KEY_STATE_STAGE_ID_TEST_SETUP = ModuleConfig::MODULE_NAME . '-test-setup';
	public const KEY_STATE_ID = 'StateId';
	public const KEY_SP_ENTITY_ID = 'spEntityId';

	protected Cache $cache;
	protected ResponderResolver $responderResolver;
	protected StateHelper $stateHelper;

	/**
	 * Initialize this filter.
	 * Validate configuration parameters.
	 *
	 * @param array $config Configuration information about this filter.
	 * @param mixed $reserved For future use.
	 * @throws CacheException
	 */
    public function __construct(
	    array             $config,
	                      $reserved,
	    Cache             $cache = null,
	    ResponderResolver $responderResolver = null,
	    StateHelper       $stateHelper = null
    )
    {
        parent::__construct($config, $reserved);
		$this->cache = $cache ?? new Cache();
		$this->responderResolver = $responderResolver ?? new ResponderResolver();
		$this->stateHelper = $stateHelper ?? new StateHelper();
    }


    /**
     * Apply filter.
     *
     * @param array &$state  The current request
     */
    public function process(array &$state): void
    {
	    $spEntityId = $this->stateHelper->resolveSpEntityId($state);

		$testId = $this->resolveTestId($spEntityId);

		if (is_null($testId)) {
			// Save state and redirect
			// TODO mivanci Bridge SSP classes
			$id = Auth\State::saveState($state, self::KEY_STATE_STAGE_ID_TEST_SETUP);
			$url = Module::getModuleURL('conformance/test/setup');
			$httpUtils = new HTTP();
			$httpUtils->redirectTrustedURL($url, [self::KEY_STATE_ID => $id, self::KEY_SP_ENTITY_ID => $spEntityId]);
		}

	    $responderCallable = $this->responderResolver->fromTestId($testId);
	    if (is_null($responderCallable)) {
		    throw new \Exception('No test responder available for test ID ' . $testId);
	    }
	    // TODO mivanci Check if responder already exists (it should, otherwise, the authproc is not set in IdP).
	    $state[Conformance::KEY_RESPONDER] = $responderCallable;
    }

	protected function resolveTestId(string $spEntityId): ?string
	{
		$testId = $this->cache->getTestId($spEntityId);

		if (is_null($testId)) {
			return null;
		}

		return (string)$testId;
	}
}
