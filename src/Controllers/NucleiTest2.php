<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\NucleiEnv;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\SspBridge;
use SimpleSAML\Module\conformance\TemplateFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * TODO mivanci Full logging
 * @psalm-suppress InternalMethod
 */
class NucleiTest2
{
    protected const KEY_NUCLEI = 'nuclei';
    protected const KEY_ASSERTION_CONSUMER_SERVICE = 'AssertionConsumerService';
    protected const KEY_LOCATION = 'Location';
    protected const KEY_ALL_TEMPLATES = 'all-templates';
    protected const KEY_TEMPLATE_EXTENSION = '.yaml';

    public function __construct(
        protected Configuration $sspConfiguration,
        protected ModuleConfiguration $moduleConfiguration,
        protected ResponderResolver $responderResolver,
        protected SspBridge $sspBridge,
        protected Helpers $helpers,
        protected TemplateFactory $templateFactory,
        protected Authorization $authorization,
        protected MetaDataStorageHandler $metaDataStorageHandler,
        protected NucleiEnv $nucleiRunner,
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

        /** @psalm-suppress MixedAssignment */
        $templateId = $request->get('templateId');
        $templateId = empty($templateId) ? null : (string)$templateId;

        if (!empty($templateId)) {
            try {
                $this->nucleiRunner->setTemplateId($templateId);
            } catch (ConformanceException $exception) {
                return new StreamedResponse(function () use ($templateId, $exception) {
                    echo "Error setting template ID $templateId. Error was: {$exception->getMessage()}";
                });
            }
        }

        $headers = ['Content-Type' =>  'text/plain', 'Content-Encoding' => 'chunked'];

        $this->nucleiRunner->enableDebug = (bool) $request->get('enableDebug');
        $this->nucleiRunner->enableVerbose = (bool) $request->get('enableVerbose');
        $this->nucleiRunner->enableOutputExport = (bool) $request->get('enableOutputExport');
        $this->nucleiRunner->enableFindingsExport = (bool) $request->get('enableFindingsExport');
        $this->nucleiRunner->enableJsonExport = (bool) $request->get('enableJsonExport');
        $this->nucleiRunner->enableJsonLExport = (bool) $request->get('enableJsonLExport');
        $this->nucleiRunner->enableSarifExport = (bool) $request->get('enableSarifExport');
        $this->nucleiRunner->enableMarkdownExport = (bool) $request->get('enableMarkdownExport');

        $token = $this->moduleConfiguration->getLocalTestRunnerToken();

        return new StreamedResponse(function () use (
            $spEntityId,
            $target,
            $acsUrl,
            $token,
            $testId,
        ): void {

        echo $this->nucleiRunner->prepareCommand($spEntityId, $target, $acsUrl, $token, $testId);die();


            $descriptors = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ];

            $process = proc_open($command, $descriptors, $pipes, $nucleiDataDir);

            // Check if the process was successfully started
            if (is_resource($process)) {
                // Close unused pipes
                fclose($pipes[0]);

                // TODO mivanci Remove command print.
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
                    $output = $this->replaceColorCodes($output);
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
            }
        },
            200,
            $headers);
    }

    /**
     * Replace ANSI color codes with HTML or other formatting. Update as needed.
     */
    protected function replaceColorCodes(string $output): string
    {
        //return $output;
        $colorCodes = array(
            '/\e\[(30|0;30)m/' => '<span class="black-text">',
            '/\e\[(31|0;31)m/' => '<span class="red-text">',
            '/\e\[1;31m/' => '<span class="bold-text red-text">',
            '/\e\[(32|0;32)m/' => '<span class="green-text">',
            '/\e\[(33|0;33)m/' => '<span class="yellow-text">',
            '/\e\[(34|0;34)m/' => '<span class="blue-text">',
            '/\e\[(35|0;35)m/' => '<span class="magenta-text">',
            '/\e\[(36|0;36)m/' => '<span class="cyan-text">',
            '/\e\[(37|0;37)m/' => '<span class="white-text">',
            '/\e\[40m/' => '<span class="black-bg">',
            '/\e\[41m/' => '<span class="red-bg">',
            '/\e\[42m/' => '<span class="green-bg;">',
            '/\e\[43m/' => '<span class="yellow-bg;">',
            '/\e\[44m/' => '<span class="blue-bg">',
            '/\e\[45m/' => '<span class="magenta-bg;">',
            '/\e\[46m/' => '<span class="cyan-bg">',
            '/\e\[47m/' => '<span class="white-bg;">',
            '/\e\[1m/' => '<span class="bold-text">',
            '/\e\[4m/' => '<span class="underline-text">',
            '/\e\[5m/' => '<span class="blink-text;">',
            '/\e\[7m/' => '<span class="blue-bg white-text">',
            '/\e\[92m/' => '<span class="green-text">',
            '/\e\[91m/' => '<span class="lightcoral-text">',
            '/\e\[1;92m/' => '<span class="bold-text green-text">',
            '/\e\[93m/' => '<span class="yellow-text">',
            '/\e\[94m/' => '<span class="blue-text">',
            '/\e\[96m/' => '<span class="lightcyan-text">',

            '/\e\[0m/' => '</span>',
        );

        return preg_replace(array_keys($colorCodes), array_values($colorCodes), $output);
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
            // Log
        }

        return new JsonResponse([]);
    }
}
