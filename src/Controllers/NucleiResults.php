<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\NoState;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\TemplateFactory;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class NucleiResults
{
    protected const KEY_SET_SP_REMOTE = 'saml20-sp-remote'; // TODO mivanci Add to SspBridge class.
    const KEY_NUCLEI = 'nuclei';
    protected MetaDataStorageHandler $metaDataStorageHandler;

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected TemplateFactory $templateFactory,
        protected Authorization $authorization,
        MetaDataStorageHandler $metaDataStorageHandler = null, // TODO mivanci Add to SspBridge.
    ) {
        $this->metaDataStorageHandler = $metaDataStorageHandler ?? MetaDataStorageHandler::getMetadataHandler();
    }

    public function index(Request $request): Response
    {
        $serviceProviders = $this->metaDataStorageHandler->getList(self::KEY_SET_SP_REMOTE);

        $selectedServiceProviderEntityId = (string) $request->get('serviceProviderEntityId');

        if ($selectedServiceProviderEntityId) {
            // Authorization for specific SP.
            $this->authorization->requireServiceProviderToken($request, $selectedServiceProviderEntityId);
        } else {
            $this->authorization->requireAdministrativeToken($request);
        }

        $results = [];
        if (
            $selectedServiceProviderEntityId &&
            file_exists($resultsDir = $this->resolveResultsDir($selectedServiceProviderEntityId))
        ) {
            // Create a RecursiveDirectoryIterator instance
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resultsDir));

            // Iterate through each file in the directory
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
        $template->data['selectedServiceProviderEntityId'] = $selectedServiceProviderEntityId;
        $template->data['results'] = $results;

        return $template;
    }

    public function download(Request $request): Response
    {
        $serviceProviderEntityId = (string) $request->get('serviceProviderEntityId');

        $this->authorization->requireServiceProviderToken($request, $serviceProviderEntityId);

        $result = (string) $request->get('result');
        $filePath = $this->resolveResultsDir($serviceProviderEntityId) . $result;

        if (! file_exists($filePath)) {
            return new Response(null, 404);
        }
        $binaryFileResponse = new BinaryFileResponse($filePath);
        $binaryFileResponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $binaryFileResponse;
    }

    // TODO mivanci move to common resolver used in NucleiTest
    protected function resolveResultsDir(string $selectedServiceProviderEntityId): ?string
    {
        $spIdentifier = hash('sha256', $selectedServiceProviderEntityId);
        $nucleiDataDir = $this->sspConfig->getPathValue('datadir', sys_get_temp_dir()) . self::KEY_NUCLEI;

        return $nucleiDataDir . DIRECTORY_SEPARATOR .
            'results' . DIRECTORY_SEPARATOR .
            $spIdentifier  . DIRECTORY_SEPARATOR;
    }
}
