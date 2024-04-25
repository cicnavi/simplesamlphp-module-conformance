<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use JsonException;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Error\NotFound;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultImageRepository;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultRepository;
use SimpleSAML\Module\conformance\Entities\Nuclei\TestResult;
use SimpleSAML\Module\conformance\Errors\AuthorizationException;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use SimpleSAML\Module\conformance\Factories\TestResultFactory;
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
        protected TestResultFactory $testResultFactory,
        protected TestResultImageRepository $testResultImageRepository,
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
            ModuleConfiguration::MODULE_NAME . ':nuclei/results/index.twig',
            Routes::PATH_TEST_RESULTS,
        );
        $template->data['serviceProviders'] = $serviceProviders;
        $template->data['results'] = $results;
        $template->data['spEntityId'] = $spEntityId;

        return $template;
    }

    /**
     * @throws ConfigurationError
     * @throws ConformanceException
     * @throws Exception
     * @throws AuthorizationException
     * @throws JsonException
     */
    public function show(int $testResultId, Request $request): Response
    {
        $row = $this->testResultRepository->getSpecificById($testResultId);
        $result = is_array($row) ? $this->testResultFactory->fromRow($row) : null;

        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':nuclei/results/show.twig',
            Routes::PATH_TEST_RESULTS,
        );

        if (is_null($result)) {
            $template->setStatusCode(404);
            return $template;
        }

        $this->authorization->requireServiceProviderToken($request, $result->spEntityId);

        /** @psalm-suppress InternalMethod, MixedAssignment */
        $spEntityId = $request->get(Conformance::KEY_SP_ENTITY_ID);
        $spEntityId = $spEntityId ? (string)$spEntityId : null;
        $backUrlParams = [];
        if ($spEntityId) {
            $backUrlParams[Conformance::KEY_SP_ENTITY_ID] = $spEntityId;
        }
        $backUrl = $this->helpers->routes()->getUrl(
            Routes::PATH_TEST_RESULTS,
            ModuleConfiguration::MODULE_NAME,
            $backUrlParams,
        );

        $images = $this->testResultImageRepository->getForTestResult(
            $result->id,
            [TestResultImageRepository::COLUMN_ID, TestResultImageRepository::COLUMN_NAME]
        );

        $template->data['result'] = $result;
        $template->data['backUrl'] = $backUrl;
        $template->data['images'] = $images;

        return $template;
    }

    public function image(int $testResultId, int $imageId, Request $request): Response
    {
        $testResultRow = $this->testResultRepository->getSpecificById($testResultId);
        $testResult = is_array($testResultRow) ? $this->testResultFactory->fromRow($testResultRow) : null;

        if (!$testResult) {
            return new Response(null, 404);
        }

        $this->authorization->requireServiceProviderToken($request, $testResult->spEntityId);

        $imageRow = $this->testResultImageRepository->getSpecificForTestResult($testResultId, $imageId);

        if (!$imageRow) {
            return new Response(null, 404);
        }

        $imageName = isset($imageRow[TestResultImageRepository::COLUMN_NAME]) ?
            (string)$imageRow[TestResultImageRepository::COLUMN_NAME] :
            $imageId . 'png';

        return new Response(
            (string)$imageRow[TestResultImageRepository::COLUMN_DATA],
            200,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'filename="' . $imageName . '"',
            ]
        );
    }

    /**
     * List result statuses in JSON format.
     *
     * @throws AuthorizationException
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

    public function getDetails(int $testResultId, Request $request): Response
    {
        $row = $this->testResultRepository->getSpecificById($testResultId);
        $result = is_array($row) ? $this->testResultFactory->fromRow($row) : null;

        if (!$result) {
            return new JsonResponse(null, 404);
        }

        $this->authorization->requireServiceProviderToken($request, $result->spEntityId);

        return new JsonResponse($result->toDetailedArray());
    }

    public function getImages(int $testResultId, Request $request): Response
    {
        $row = $this->testResultRepository->getSpecificById($testResultId);
        $result = is_array($row) ? $this->testResultFactory->fromRow($row) : null;

        if (!$result) {
            return new JsonResponse(null, 404);
        }

        $this->authorization->requireServiceProviderToken($request, $result->spEntityId);

        $imageRows = $this->testResultImageRepository->getForTestResult(
            $result->id,
            [TestResultImageRepository::COLUMN_ID, TestResultImageRepository::COLUMN_NAME]
        );

        // Add URL for each image.
        array_walk($imageRows, function (array &$imageRow) use ($testResultId) {
            $imageRow['url'] = $this->helpers->routes()->getUrl(Routes::PATH_TEST_RESULTS) .
                "/show/$testResultId/image/{$imageRow[TestResultImageRepository::COLUMN_ID]}";
        });

        return new JsonResponse($imageRows);
    }

    /**
     * @throws ConformanceException
     * @throws JsonException
     */
    protected function getNormalizedResults(?string $spEntityId = null, bool $latestOnly = false): array
    {
        $results = [];
        $rows = $latestOnly ?
            $this->testResultRepository->getLatest($spEntityId) :
            $this->testResultRepository->get($spEntityId);

        foreach ($rows as $row) {
            $results[] = ($this->testResultFactory->fromRow($row))->jsonSerialize();
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
