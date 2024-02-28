<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use Exception;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\NoState;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Cache;
use SimpleSAML\Module\conformance\Helpers\State;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\SspBridge;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestSetup
{
    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected Cache $cache,
        protected State $stateHelper,
        protected ResponderResolver $responderResolver,
        protected Authorization $authorization,
        protected SspBridge $sspBridge,
        protected MetaDataStorageHandler $metaDataStorageHandler,
    ) {
    }

    /**
     * @throws ConfigurationError|Exception
     * @psalm-suppress InternalMethod,MixedAssignment
     */
    public function setup(Request $request): Response
    {
        $testId = $request->get(Conformance::KEY_TEST_ID);
        $testId = empty($testId) ? null : (string)$testId;

        $spEntityId = $request->get(Conformance::KEY_SP_ENTITY_ID);
        $spEntityId = empty($spEntityId) ? null : (string)$spEntityId;

        // Call from external process which sets next test for particular SP.
        if ($testId && $spEntityId) {
            // We need to authorize this specific call, since it is external.
            $this->authorization->requireServiceProviderToken($request, $spEntityId);
            // Simple validation of test ID and SP entity ID.
            try {
                $this->responderResolver->fromTestIdOrThrow($testId);
                $this->metaDataStorageHandler->getMetaData($spEntityId, SspBridge::KEY_SET_SP_REMOTE);
            } catch (\Throwable $exception) {
                return new JsonResponse(
                    [
                        'status' => 'error',
                        'message' => "Test ID or SP Entity ID not valid ({$exception->getMessage()})."
                    ]
                );
            }
            $this->cache->setTestId($testId, $spEntityId);
            return new JsonResponse(['status' => 'ok']);
        }

        // Call from authproc filter.
        // Leave this unauthorized so any registered SP can utilize manual test initiated from the SP.
        $stateId = $request->query->get(Conformance::KEY_STATE_ID);
        $stateId = empty($stateId) ? null : (string)$stateId;

        if (is_null($stateId)) {
            throw new Exception('Missing required StateId query parameter.');
        }

        $state = $this->sspBridge->auth()->state()->loadState($stateId, Conformance::KEY_STATE_STAGE_ID_TEST_SETUP);

        $spEntityId = $this->stateHelper->resolveSpEntityId($state);
        if ($testId) {
            $responderCallable = $this->responderResolver->fromTestIdOrThrow($testId);
            $this->stateHelper->setResponder($state, $responderCallable);
            return new RunnableResponse([ProcessingChain::class, 'resumeProcessing'], [$state]);
        }

        // We need to show a page to a user
        $template = new Template($this->sspConfig, ModuleConfiguration::MODULE_NAME . ':test/setup.twig');
        $template->data[Conformance::KEY_SP_ENTITY_ID] = $spEntityId;
        $template->data[Conformance::KEY_STATE_ID] = $stateId;
        return $template;
    }
}
