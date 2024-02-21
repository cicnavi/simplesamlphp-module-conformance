<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use Exception;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\NoState;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Cache;
use SimpleSAML\Module\conformance\Helpers\StateHelper;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ManualTest
{
    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected Cache $cache,
        protected StateHelper $stateHelper,
        protected ResponderResolver $responderResolver,
        protected Authorization $authorization,
    ) {
    }

    /**
     * @throws NoState
     * @throws ConfigurationError
     * @throws Exception
     */
    public function setup(Request $request): Response
    {
        $testId = $request->get(Conformance::KEY_TEST_ID);
        $spEntityId = $request->get(Conformance::KEY_SP_ENTITY_ID);

        // Call from external process which sets next test for particular SP.
        // TODO mivanci Consider moving this to separate route.
        if ($testId && $spEntityId) {
            // We need to authorize this specific call, since it is external.
            $this->authorization->requireServiceProviderToken($request, $spEntityId);
            // TODO mivanci Validate $testId and $spEntityId
            $this->cache->setTestId($testId, $spEntityId);
            return new JsonResponse(['status' => 'ok']);
        }

        // Call from authproc filter.
        // Leave this unauthorized so any registered SP can utilize manual test initiated from the SP.
        $stateId = $request->query->get(Conformance::KEY_STATE_ID);
        if (is_null($stateId)) {
            throw new Exception('Missing required StateId query parameter.');
        }

        $state = State::loadState($stateId, Conformance::KEY_STATE_STAGE_ID_TEST_SETUP);
        if (is_null($state)) {
            throw new Exception('Missing state for ' . Conformance::KEY_STATE_STAGE_ID_TEST_SETUP);
        }

        $spEntityId = $this->stateHelper->resolveSpEntityId($state);
        if ($testId) {
            $responderCallable = $this->responderResolver->fromTestId($testId);
            if (is_null($responderCallable)) {
                throw new Exception('No test responder available for test ID ' . $testId);
            }
            // TODO mivanci Check if responder already exists (it should, otherwise, the authproc is not set in IdP).
            $state[Conformance::KEY_RESPONDER] = $responderCallable;
            return new RunnableResponse([ProcessingChain::class, 'resumeProcessing'], [$state]);
        }

        // We need to show a page to a user
        $template = new Template($this->sspConfig, ModuleConfiguration::MODULE_NAME . ':test/manual.twig');
        $template->data[Conformance::KEY_SP_ENTITY_ID] = $spEntityId;
        $template->data[Conformance::KEY_STATE_ID] = $stateId;
        return $template;
    }
}
