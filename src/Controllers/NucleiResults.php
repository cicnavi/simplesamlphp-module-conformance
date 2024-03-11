<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Entities\Nuclei\TestResultStatus;
use SimpleSAML\Module\conformance\Errors\AuthorizationException;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\NucleiEnv;
use SimpleSAML\Module\conformance\SspBridge;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        protected NucleiEnv $nucleiEnv,
        protected Helpers $helpers,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ConfigurationError
     * @throws Exception
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $serviceProviders = $this->metaDataStorageHandler->getList(SspBridge::KEY_SET_SP_REMOTE);

        /** @psalm-suppress InternalMethod */
        $selectedSpEntityId = (string) $request->get('spEntityId');

        if ($selectedSpEntityId) {
            // Authorization for specific SP.
            $this->authorization->requireServiceProviderToken($request, $selectedSpEntityId);
        } else {
            $this->authorization->requireAdministrativeToken($request);
        }

        $files = [];
        if (
            $selectedSpEntityId &&
            file_exists($resultsDir = $this->nucleiEnv->getSpResultsDir($selectedSpEntityId))
        ) {
            $files = $this->helpers->filesystem()->listFilesInDirectory(
                $resultsDir,
                Helpers\Filesystem::KEY_SORT_DESC,
            );
        }

        $artifacts = [];

        // Key by datetime
        foreach ($files as $artifact) {
            $elements = explode(DIRECTORY_SEPARATOR, $artifact, 2);

            if (count($elements) !== 2) {
                continue;
            }

            if (isset($artifacts[$elements[0]])) {
                $artifacts[$elements[0]][] =  $elements[1];
            } else {
                $artifacts[$elements[0]] = [$elements[1]];
            }
        }

        // TODO mivanci move to factory
        $latestStatus = null;
        $latestTimestamp = key($artifacts);
        $latestArtifacts = current($artifacts);
        if (
            $selectedSpEntityId &&
            (!is_null($latestTimestamp)) &&
            is_array($latestArtifacts)
        ) {
            $parsedJsonResult = null;
            if (
                in_array(NucleiEnv::FILE_JSON_EXPORT, $latestArtifacts) &&
                file_exists(
                    $jsonResultPath = $this->helpers->filesystem()->getPathFromElements(
                        $this->nucleiEnv->getSpResultsDir($selectedSpEntityId),
                        strval($latestTimestamp),
                        NucleiEnv::FILE_JSON_EXPORT
                    )
                ) &&
                $jsonResultContent = file_get_contents($jsonResultPath)
            ) {
                try {
                    /** @var array $parsedJsonResult */
                    $parsedJsonResult = json_decode($jsonResultContent, true, 512, JSON_THROW_ON_ERROR);
                } catch (\Throwable $exception) {
                    $this->logger->error('Unable to parse exported Nuclei JSON result for ' . $selectedSpEntityId);
                }
            }

            $latestStatus = new TestResultStatus($selectedSpEntityId, intval($latestTimestamp), $parsedJsonResult);
        }

//        dd($artifacts, $files, );

        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':nuclei/results.twig',
            Routes::PATH_TEST_RESULTS,
        );
        $template->data['serviceProviders'] = $serviceProviders;
        $template->data['selectedSpEntityId'] = $selectedSpEntityId;
        $template->data['artifacts'] = $artifacts;
        $template->data['latestStatus'] = $latestStatus;

        return $template;
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
