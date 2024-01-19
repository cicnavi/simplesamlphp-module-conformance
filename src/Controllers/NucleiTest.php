<?php

namespace SimpleSAML\Module\conformance\Controllers;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\conformance\ModuleConfig;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * TODO mivanci Full logging
 */
class NucleiTest
{
    protected const KEY_NUCLEI = 'nuclei';

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfig $moduleConfig,
    ) {
    }

    public function setup(Request $request): Response
    {

//        $output .= `nuclei -headless -u simplesamlphp-sp.maiv1.incubator.geant.org -duc -fr -ms -t templates/ -json-export output.json`;
//        $output .= `; pwd; nuclei -target localhost.markoivancic.from.hr -headless`;
        $canRunTest = (bool) $request->get('run', false);
        $template = new Template($this->sspConfig, ModuleConfig::MODULE_NAME . ':nuclei-test.twig');
        $template->data['output'] = Translate::noop('Waiting for output...');
        $template->data['canRunTest'] = $canRunTest;

        return $template;
    }

    // TODO mivanci control how many times can this be ran at the same time.
    public function run(): StreamedResponse
    {
        $nucleiDataDir = $this->sspConfig->getPathValue('datadir', sys_get_temp_dir()) . self::KEY_NUCLEI;
        $nucleiScreenshotsDir = $nucleiDataDir . DIRECTORY_SEPARATOR . 'screenshots';

        $nucleiPublicDir = $this->moduleConfig->getModuleRootDirectory() . DIRECTORY_SEPARATOR . 'public' .
            DIRECTORY_SEPARATOR . self::KEY_NUCLEI;

//        dd($nucleiDataDir, $nucleiPublicDir, $nucleiScreenshotsDir);

        $headers = ['Content-Type' =>  'text/plain', 'Content-Encoding' => 'chunked'];

        return new StreamedResponse(function() use ($nucleiDataDir): void  {
            $command = "cd $nucleiDataDir; pwd; nuclei -target localhost.markoivancic.from.hr -headless";
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

                // Read the output stream and send it to the browser in chunks
                while (!feof($pipes[1])) {
                    // Read chunk, adjust size as needed
                    $output = fread($pipes[1], 1024);
                    // Keep a token for newline control character
                    $output = str_replace("\n", '--newlinetoken--', $output);
                    // Get rid of other special chars
                    $output = filter_var($output, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
                    // Get newlines back
                    $output = str_replace("--newlinetoken--", "\n", $output);
                    echo $output;
                    flush();
                    ob_flush();
                }

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
}