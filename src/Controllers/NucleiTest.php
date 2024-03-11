<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\NucleiEnv;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\SpConsentHandler;
use SimpleSAML\Module\conformance\SspBridge;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @psalm-suppress InternalMethod
 */
class NucleiTest
{
    protected const KEY_ASSERTION_CONSUMER_SERVICE = 'AssertionConsumerService';
    protected const KEY_LOCATION = 'Location';

    public function __construct(
        protected Configuration $sspConfiguration,
        protected ModuleConfiguration $moduleConfiguration,
        protected ResponderResolver $responderResolver,
        protected SspBridge $sspBridge,
        protected Helpers $helpers,
        protected TemplateFactory $templateFactory,
        protected Authorization $authorization,
        protected MetaDataStorageHandler $metaDataStorageHandler,
        protected NucleiEnv $nucleiEnv,
        protected SpConsentHandler $spConsentHandler,
        protected LoggerInterface $logger,
    ) {
    }

    public function setup(): Response
    {
        $this->authorization->requireSimpleSAMLphpAdmin(true);

        $serviceProviders = $this->metaDataStorageHandler->getList(SspBridge::KEY_SET_SP_REMOTE);

        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':nuclei/test/setup.twig',
            Routes::PATH_TEST_NUCLEI_SETUP,
        );
        $template->data['serviceProviders'] = $serviceProviders;

        return $template;
    }

    public function run(Request $request): Response
    {
        /** @psalm-suppress MixedAssignment */
        $testId = $request->get('testId');
        $testId = empty($testId) ? null : (string)$testId;

        if ($testId && !$this->responderResolver->fromTestId($testId)) {
            return new StreamedResponse(function () {
                echo 'Invalid test ID.';
            });
        }

        /** @psalm-suppress MixedAssignment */
        $spEntityId = $request->get(Conformance::KEY_SP_ENTITY_ID);
        $spEntityId = empty($spEntityId) ? null : (string)$spEntityId;

        if (empty($spEntityId)) {
            return new StreamedResponse(function () {
                echo 'Invalid SP selected.';
            });
        }

        $this->authorization->requireServiceProviderToken($request, $spEntityId);

        try {
            $spMetadata = $this->metaDataStorageHandler->getMetaDataConfig($spEntityId, SspBridge::KEY_SET_SP_REMOTE);
        } catch (\Throwable $exception) {
            return new StreamedResponse(function () {
                echo 'No metadata for provided SP.';
            });
        }

        // We have trusted SP. Handle consent if needed.
        if (
            $this->spConsentHandler->shouldValidateConsentForSp($spEntityId) &&
            (! $this->spConsentHandler->isConsentedForSp($spEntityId))
        ) {
            $message = 'SP consent is required to run tests. ';
            if (! $this->spConsentHandler->isRequestedForSp($spEntityId)) {
                try {
                    $this->spConsentHandler->requestForSp($spEntityId, $spMetadata->toArray());
                    $message .= 'Request for consent has now been sent.';
                } catch (\Throwable $exception) {
                    $message .= 'Error requesting consent: ' . $exception->getMessage();
                }
            } else {
                $message .= 'Consent has already been requested, but still not accepted.';
            }
            return new StreamedResponse(function () use ($message) {
                echo $message;
            });
        }

        try {
            /** @psalm-suppress MixedAssignment */
            $acsUrl = $request->get('acsUrl') ??
                $spMetadata->getDefaultEndpoint(self::KEY_ASSERTION_CONSUMER_SERVICE);
        } catch (\Throwable $exception) {
            return new StreamedResponse(function () {
                echo "Could not resolve Assertion Consumer Service (ACS).";
            });
        }

        if (is_array($acsUrl)) {
            $acsUrl = (string)($acsUrl[self::KEY_LOCATION] ?? '');
        } else {
            $acsUrl = (string)$acsUrl;
        }

        $target = parse_url($acsUrl, PHP_URL_HOST);

        if (empty($target)) {
            return new StreamedResponse(function () use ($acsUrl) {
                echo "Could not extract target from ACS: $acsUrl.";
            });
        }

        // TODO mivanci remove if not necessary.
        /** @psalm-suppress MixedAssignment */
//        $templateId = $request->get('templateId');
//        $templateId = empty($templateId) ? null : (string)$templateId;
//
//        if (!empty($templateId)) {
//            try {
//                $this->nucleiEnv->setTemplateId($templateId);
//            } catch (ConformanceException $exception) {
//                return new StreamedResponse(function () use ($templateId, $exception) {
//                    echo "Error setting template ID $templateId. Error was: {$exception->getMessage()}";
//                });
//            }
//        }

        $headers = ['Content-Type' =>  'text/plain', 'Content-Encoding' => 'chunked'];

        $this->nucleiEnv->enableDebug = (bool) $request->get('enableDebug');
        $this->nucleiEnv->enableVerbose = (bool) $request->get('enableVerbose');
        // TODO mivanci remove if not necessary.
//        $this->nucleiEnv->enableOutputExport = (bool) $request->get('enableOutputExport');
//        $this->nucleiEnv->enableFindingsExport = (bool) $request->get('enableFindingsExport');
//        $this->nucleiEnv->enableJsonExport = (bool) $request->get('enableJsonExport');
//        $this->nucleiEnv->enableJsonLExport = (bool) $request->get('enableJsonLExport');
//        $this->nucleiEnv->enableSarifExport = (bool) $request->get('enableSarifExport');
//        $this->nucleiEnv->enableMarkdownExport = (bool) $request->get('enableMarkdownExport');

        $token = $this->moduleConfiguration->getLocalTestRunnerToken();

        $command = $this->nucleiEnv->prepareCommand($spEntityId, $target, $acsUrl, $token, $testId);

        $this->logger->debug('Nuclei command to run: ' . $command);

        return new StreamedResponse(
            function () use ($command, $token): void {
                $descriptors = [
                    0 => ['pipe', 'r'],  // stdin
                    1 => ['pipe', 'w'],  // stdout
                    2 => ['pipe', 'w']   // stderr
                ];

                $process = proc_open($command, $descriptors, $pipes, $this->nucleiEnv->dataDir);

                // Check if the process was successfully started
                if (!is_resource($process)) {
                    echo "Unable to open process needed to run the command.";
                    flush();
                    ob_flush();
                    return;
                }

                // Close unused pipes
                fclose($pipes[0]);

                // Command print.
                echo str_replace([$token], 'hidden', $command);
                flush();
                ob_flush();

                // Read the output stream and send it to the browser in chunks
                while (!feof($pipes[1])) {
                    // Read chunk, adjust size as needed
                    $output = fread($pipes[1], 1024);
                    // Keep a token for newline control character
                    //$output = str_replace("\n", '--newlinetoken--', $output);
                    // Replace common color codes.
                    $output = $this->helpers->shell()->replaceColorCodes($output);
                    // Get rid of other special chars
                    // phpcs:ignore
                    //$output = filter_var($output, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
                    // Get newlines back
                    //$output = str_replace("--newlinetoken--", "\n", $output);
                    echo $output;
                    flush();
                    ob_flush();
                }

                echo "Done! \n";
                flush();
                ob_flush();

                // Close the process
                fclose($pipes[1]);
                fclose($pipes[2]);

            //                $procStatus = proc_get_status($process);
            //                echo "Process status: " . var_export($procStatus, true);

                $exitCode = proc_close($process);

                echo "Exit code: $exitCode \n";
                flush();
                ob_flush();
            },
            200,
            $headers
        );
    }

    /**
     * Fetch (any) ACSs for specific SP Entity ID.
     */
    public function fetchAcss(Request $request): JsonResponse
    {
        /** @psalm-suppress MixedAssignment */
        $spEntityId = $request->get('spEntityId');
        $spEntityId = empty($spEntityId) ? null : (string)$spEntityId;

        if (is_null($spEntityId)) {
            return new JsonResponse([]);
        }

        try {
            $spMetadataConfig = $this->metaDataStorageHandler
                ->getMetaDataConfig($spEntityId, SspBridge::KEY_SET_SP_REMOTE);
            $acsArr = $spMetadataConfig->getEndpoints(self::KEY_ASSERTION_CONSUMER_SERVICE);

            return new JsonResponse(array_unique(array_column($acsArr, self::KEY_LOCATION)));
        } catch (\Throwable $exception) {
            $this->logger->error('Error fetching ACSs: ' . $exception->getMessage());
        }

        return new JsonResponse([]);
    }
}
