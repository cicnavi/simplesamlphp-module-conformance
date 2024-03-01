<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Errors\AuthorizationException;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\NucleiEnv;
use SimpleSAML\Module\conformance\SspBridge;
use SimpleSAML\Module\conformance\TemplateFactory;
use SplFileInfo;
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

        $results = [];
        if (
            $selectedSpEntityId &&
            file_exists($resultsDir = $this->nucleiEnv->getSpResultsDir($selectedSpEntityId))
        ) {
            // Create a RecursiveDirectoryIterator instance
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resultsDir));

            // Iterate through each file in the directory
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                // Check if it's a regular file (not a directory)
                if ($file->isFile()) {
                    $results[] = str_replace($resultsDir, '', $file->getPathname()) ;
                }
            }

            arsort($results);
        }

        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':nuclei/results.twig',
            Routes::PATH_TEST_RESULTS,
        );
        $template->data['serviceProviders'] = $serviceProviders;
        $template->data['selectedSpEntityId'] = $selectedSpEntityId;
        $template->data['results'] = $results;

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
