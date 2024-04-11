<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultRepository;
use SimpleSAML\Module\conformance\Entities\Nuclei\TestResultStatus;
use SimpleSAML\Module\conformance\Errors\AuthorizationException;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\Nuclei\Env;
use SimpleSAML\Module\conformance\SspBridge;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class NucleiResults
{
    public const KEY_NUCLEI = 'nuclei';

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected TemplateFactory $templateFactory,
        protected Authorization $authorization,
        protected MetaDataStorageHandler $metaDataStorageHandler,
        protected Env $nucleiEnv,
        protected Helpers $helpers,
        protected LoggerInterface $logger,
        protected TestResultRepository $testResultRepository,
    ) {
    }

    /**
     * @throws ConfigurationError
     * @throws Exception
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $this->authorization->requireAdministrativeToken($request);

        $serviceProviders = $this->metaDataStorageHandler->getList(SspBridge::KEY_SET_SP_REMOTE);

        /** @psalm-suppress InternalMethod, MixedAssignment */
        $spEntityId = $request->get('spEntityId');
        $spEntityId = $spEntityId ? (string)$spEntityId : null;
        /** @psalm-suppress InternalMethod */
        $latestOnly = (bool)$request->get('latestOnly');

        $results = $spEntityId ?
            $this->getNormalizedResults($spEntityId, $latestOnly) :
            $this->getNormalizedResults(null, true);

        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':nuclei/results.twig',
            Routes::PATH_TEST_RESULTS,
        );
        $template->data['serviceProviders'] = $serviceProviders;
        $template->data['results'] = $results;
        $template->data['spEntityId'] = $spEntityId;

        return $template;
    }

    /**
     * @throws AuthorizationException
     * @throws \JsonException
     */
    public function get(Request $request): Response
    {
        /** @psalm-suppress InternalMethod, MixedAssignment */
        $spEntityId = $request->get('spEntityId');
        $spEntityId = $spEntityId ? (string)$spEntityId : null;
        /** @psalm-suppress InternalMethod */
        $latestOnly = (bool)$request->get('latestOnly');

        if ($spEntityId) {
            // Authorization for specific SP.
            $this->authorization->requireServiceProviderToken($request, $spEntityId);
        } else {
            $this->authorization->requireAdministrativeToken($request);
        }

        return new JsonResponse($this->getNormalizedResults($spEntityId, $latestOnly));
    }

    protected function getNormalizedResults(?string $spEntityId = null, bool $latestOnly = false): array
    {
        $results = [];
        $rows = $latestOnly ?
            $this->testResultRepository->getLatest($spEntityId) :
            $this->testResultRepository->get($spEntityId);

        foreach ($rows as $row) {
            $results[] = (new TestResultStatus(
                (int)$row[TestResultRepository::COLUMN_ID],
                (string)$row[TestResultRepository::COLUMN_ENTITY_ID],
                (int)$row[TestResultRepository::COLUMN_HAPPENED_AT],
                $row[TestResultRepository::COLUMN_NUCLEI_JSON_RESULT] ?
                    (string)$row[TestResultRepository::COLUMN_NUCLEI_JSON_RESULT] : null,
                $row[TestResultRepository::COLUMN_NUCLEI_FINDINGS] ?
                    (string)$row[TestResultRepository::COLUMN_NUCLEI_FINDINGS] : null,
            ))->jsonSerialize();
        }

        return $results;
    }

    /**
     * @psalm-suppress InternalMethod
     * @throws AuthorizationException
     */
    public function download(Request $request): Response
    {
        $spEntityId = (string) $request->get('spEntityId');

        $this->authorization->requireServiceProviderToken($request, $spEntityId);

        $result = (string) $request->get('result');
        $filePath = $this->nucleiEnv->getSpResultsDir($spEntityId) . DIRECTORY_SEPARATOR . $result;

        if (! file_exists($filePath)) {
            return new Response(null, 404);
        }
        $binaryFileResponse = new BinaryFileResponse($filePath);
        $binaryFileResponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $binaryFileResponse;
    }
}
