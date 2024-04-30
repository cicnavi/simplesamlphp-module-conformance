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
use SimpleSAML\Module\conformance\Nuclei\TestRunner;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\SspBridge;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function noop;

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
        protected TestRunner $nucleiTestRunner,
        protected MetaDataStorageHandler $metaDataStorageHandler,
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
                echo noop('Invalid test ID.');
            });
        }

        /** @psalm-suppress MixedAssignment */
        $spEntityId = $request->get(Conformance::KEY_SP_ENTITY_ID);
        $spEntityId = empty($spEntityId) ? null : (string)$spEntityId;

        if (empty($spEntityId)) {
            return new StreamedResponse(function () {
                echo noop('Invalid SP selected.');
            });
        }

        /** @psalm-suppress MixedAssignment */
        $acsUrl = $request->get('acsUrl');
        $acsUrl = empty($acsUrl) ? null : (string)$acsUrl;

        $this->authorization->requireServiceProviderToken($request, $spEntityId);

        $token = $this->moduleConfiguration->getLocalTestRunnerToken();
        $this->nucleiTestRunner->env->enableDebug = (bool) $request->get('enableDebug');
        $this->nucleiTestRunner->env->enableVerbose = (bool) $request->get('enableVerbose');

        try {
            $command = $this->nucleiTestRunner->prepareCommand($token, $spEntityId, $acsUrl, $testId);
        } catch (\Throwable $exception) {
            return new StreamedResponse(function () use ($exception) {
                echo noop('Error while preparing command: ') . $exception->getMessage();
            });
        }

        $headers = ['Content-Type' =>  'text/plain', 'Content-Encoding' => 'chunked'];

        $this->logger->debug('Nuclei command to run: ' . $command);

        return new StreamedResponse(
            function () use ($spEntityId, $command): void {
                $descriptors = [
                    0 => ['pipe', 'r'],  // stdin
                    1 => ['pipe', 'w'],  // stdout
                    2 => ['pipe', 'w']   // stderr
                ];

                $process = proc_open($command, $descriptors, $pipes, $this->nucleiTestRunner->env->dataDir);

                // Check if the process was successfully started
                if (!is_resource($process)) {
                    echo noop('Unable to open process needed to run the command.');
                    flush();
                    ob_flush();
                    return;
                }

                // Close unused pipes
                fclose($pipes[0]);

                // Command print.
            //                echo str_replace([$token], 'hidden', $command);
            //                flush();
            //                ob_flush();

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

                $this->nucleiTestRunner->persistLatestResults($spEntityId);

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
