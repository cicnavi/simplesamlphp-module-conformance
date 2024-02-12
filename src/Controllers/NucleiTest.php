<?php

namespace SimpleSAML\Module\conformance\Controllers;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\ModuleConfig;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * TODO mivanci Full logging
 */
class NucleiTest
{
    protected const KEY_NUCLEI = 'nuclei';

    protected const KEY_SET_SP_REMOTE = 'saml20-sp-remote';

    protected const KEY_AssertionConsumerService = 'AssertionConsumerService';
    const KEY_Location = 'Location';

    protected MetaDataStorageHandler $metaDataStorageHandler;

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfig $moduleConfig,
        protected ResponderResolver $responderResolver,
        MetaDataStorageHandler $metaDataStorageHandler = null,
    ) {
        $this->metaDataStorageHandler = $metaDataStorageHandler ?? MetaDataStorageHandler::getMetadataHandler();
    }

    public function setup(Request $request): Response
    {
        $serviceProviders = $this->metaDataStorageHandler->getList(self::KEY_SET_SP_REMOTE);

        $template = new Template($this->sspConfig, ModuleConfig::MODULE_NAME . ':nuclei-test.twig');
        $template->data['serviceProviders'] = $serviceProviders;

        return $template;
    }

    // TODO mivanci control how many times can this be ran at the same time.
    public function run(Request $request): Response
    {
        $testId = $request->get('test-type-id');
        if (!$this->responderResolver->fromTestId($testId)) {
            return new StreamedResponse(function() {echo 'Invalid test selected.';});
        }
        $spEntityId = $request->get('service-provider-entity-id');
        if (!$spEntityId) {
            return new StreamedResponse(function() {echo 'Invalid SP selected.';});
        }

        try {
            $spMetadata = $this->metaDataStorageHandler->getMetaDataConfig($spEntityId, self::KEY_SET_SP_REMOTE);
        } catch (\Throwable $exception) {
            return new StreamedResponse(function() {echo 'No metadata for provided SP.';});
        }

        $acsUrl = $request->get('assertion-consumer-service-url') ?? $spMetadata->getDefaultEndpoint(self::KEY_AssertionConsumerService);
        if (is_array($acsUrl)) {
            $acsUrl = (string)$acsUrl[self::KEY_Location] ?? '';
        } else {
            $acsUrl = (string) $acsUrl;
        }

        $target = parse_url($acsUrl, PHP_URL_HOST);

        if (empty($target)) {
            return new StreamedResponse(function() {echo 'Could not extract target.';});
        }

        $nucleiDataDir = $this->sspConfig->getPathValue('datadir', sys_get_temp_dir()) . self::KEY_NUCLEI;
        $nucleiScreenshotsDir = $nucleiDataDir . DIRECTORY_SEPARATOR . 'screenshots';

        $nucleiPublicDir = $this->moduleConfig->getModuleRootDirectory() . DIRECTORY_SEPARATOR . 'public' .
            DIRECTORY_SEPARATOR . self::KEY_NUCLEI;
        $nucleiTemplatesDir = $nucleiPublicDir . DIRECTORY_SEPARATOR . 'templates/samltest.yaml';

        // TODO
        // Extract target from ACS

        $headers = ['Content-Type' =>  'text/plain', 'Content-Encoding' => 'chunked'];

        return new StreamedResponse(function() use (
            $nucleiDataDir, $nucleiTemplatesDir, $request, $testId, $spEntityId, $target,
        ): void  {

//            die(var_dump($request->get('test-type')));
            //TODO mivanci Move to separate service (Nuclei Shell Runner)
            // User -debug for -debug
            $command = "cd $nucleiDataDir;" .
                "nuclei -target $target " .
                "-env-vars -headless -matcher-status -follow-redirects -disable-update-check " .
                "-templates $nucleiTemplatesDir " .
                "-var TEST_ID=$testId -var SP_ENTITY_ID=$spEntityId " .
                "2>&1"
            ;

            $descriptors = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ];

            $process = proc_open($command, $descriptors, $pipes);

            // Check if the process was successfully started
            if (is_resource($process)) {
                // Close unused pipes
                fclose($pipes[0]);

                echo $command;
                flush();
                ob_flush();

                // Read the output stream and send it to the browser in chunks
                while (!feof($pipes[1])) {
                    // Read chunk, adjust size as needed
                    $output = fread($pipes[1], 2024);
                    // Keep a token for newline control character
                    //$output = str_replace("\n", '--newlinetoken--', $output);
                    // Replace common color codes.
                    $output = $this->replaceColorCodes($output);
                    // Get rid of other special chars
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
                $return_value = proc_close($process);
            }
        },
            200,
            $headers
        );
    }

    /**
     * Replace ANSI color codes with HTML or other formatting. Update as needed.
     */
    protected function replaceColorCodes(string $output): string {
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
        $spEntityId = $request->get('spEntityId') ?? null;

        if (!$spEntityId) {
            return new JsonResponse([]);
        }

        try {
            $spMetadataConfig = $this->metaDataStorageHandler->getMetaDataConfig($spEntityId, self::KEY_SET_SP_REMOTE);
            $acsArr = $spMetadataConfig->getEndpoints(self::KEY_AssertionConsumerService);

            return new JsonResponse(array_unique(array_column($acsArr, self::KEY_Location)));

        } catch (\Throwable $exception) {
            // Log
        }

        return new JsonResponse([]);
    }
}