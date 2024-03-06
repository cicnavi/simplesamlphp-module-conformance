<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Errors\ConformanceException;

class NucleiEnv
{
    public const KEY_NUCLEI = 'nuclei';
    public const KEY_TEMPLATE_EXTENSION = '.yaml';

    public const FILE_OUTPUT_EXPORT = 'output.txt';
    public const FILE_FINDINGS_EXPORT = 'findings.txt';
    public const FILE_JSON_EXPORT = 'json-output.json';
    public const FILE_JSONL_EXPORT = 'jsonl-output.json';
    public const FILE_SARIF_EXPORT = 'sarif-output.json';
    public const DIR_MARKDOWN_EXPORT = 'markdown';
    public const NUCLEI_TEMPLATE_SAML_RAW_ALL = 'saml-raw-all';
    public const NUCLEI_TEMPLATE_SAML_HEADLESS_ALL = 'saml-headless-all';

    public readonly string $dataDir;
    public readonly string $publicDir;
    public readonly string $templatesDir;
    public readonly string $conformanceIdpBaseUrl;
    public readonly string $conformanceIdpHostname;
    public readonly int $numberOfResultsToKeepPerSp;

    public bool $enableDebug = true;
    public bool $enableVerbose = true;
    public bool $enableOutputExport = true;
    public bool $enableFindingsExport = true;
    public bool $enableJsonExport = true;
    public bool $enableJsonLExport = false;
    public bool $enableSarifExport = false;
    public bool $enableMarkdownExport = false;

    public ?string $templateId = null;

    public function __construct(
        protected Configuration $sspConfiguration,
        protected ModuleConfiguration $moduleConfiguration,
        protected Helpers $helpers,
        protected SspBridge $sspBridge,
    ) {
        $this->dataDir = (
            $this->sspConfiguration->getPathValue(ModuleConfiguration::KEY_DATADIR) ?? sys_get_temp_dir()
        ) . self::KEY_NUCLEI;
        $this->publicDir = $this->moduleConfiguration->getModuleRootDirectory() . DIRECTORY_SEPARATOR . 'public' .
            DIRECTORY_SEPARATOR . self::KEY_NUCLEI;
        $this->templatesDir = $this->publicDir . DIRECTORY_SEPARATOR . 'templates';
        $this->conformanceIdpBaseUrl = $this->moduleConfiguration->getConformanceIdpBaseUrl() ??
            $this->sspBridge->utils()->http()->getBaseURL();
        $this->conformanceIdpHostname = $this->moduleConfiguration->getConformanceIdpHostname() ??
            $this->sspBridge->utils()->http()->getSelfHost();
        $this->numberOfResultsToKeepPerSp = $this->moduleConfiguration->getNumberOfResultsToKeepPerSp();
    }

    /**
     * phpcs:disable
     */
    public function prepareCommand(
        string $spEntityId,
        string $target,
        string $ascUrl,
        string $token,
        string $testId = null,
    ): string {
        $spTestResultsDir = $this->getSpTestResultsDir($spEntityId);
        $fileName = $this->helpers->filesystem()->cleanFilename($spEntityId);

        // Escape shell args.
        $spEntityId = escapeshellarg($spEntityId);
        $target = escapeshellarg($target);
        $acsUrl = escapeshellarg($ascUrl);
        $token = escapeshellarg($token);
        $testId = empty($testId) ? null : escapeshellarg($testId);

        // First use the raw HTTP template to run the tests.
        $this->templateId = self::NUCLEI_TEMPLATE_SAML_RAW_ALL;

        $command =
            "mkdir -p $spTestResultsDir; " .
            "nuclei -target $target " .
            "-env-vars -headless -matcher-status -follow-redirects -disable-update-check -timestamp " .
            "-templates {$this->getTemplatesPath()} " .
            "-var SP_ENTITY_ID=$spEntityId " .
            "-var CONSUMER_URL=$acsUrl " .
            "-var CONFORMANCE_IDP_BASE_URL=$this->conformanceIdpBaseUrl " .
            "-var CONFORMANCE_IDP_HOSTNAME=$this->conformanceIdpHostname " .
            "-var RESULT_OUTPUT_DIR=$spTestResultsDir " .
            "-var FILENAME=$fileName " .
            "-var TOKEN=$token " .
            ($testId ? "-var TEST_ID=$testId " : '') .
            ($this->enableFindingsExport ? "-output {$this->helpers->filesystem()->getPathFromElements($spTestResultsDir, self::FILE_FINDINGS_EXPORT)} " : '') .
            ($this->enableJsonExport ? "-json-export {$this->helpers->filesystem()->getPathFromElements($spTestResultsDir, self::FILE_JSON_EXPORT)} " : '') .
            ($this->enableJsonLExport ? "-jsonl-export {$this->helpers->filesystem()->getPathFromElements($spTestResultsDir, self::FILE_JSONL_EXPORT)} " : '') .
            ($this->enableSarifExport ? "-sarif-export {$this->helpers->filesystem()->getPathFromElements($spTestResultsDir, self::FILE_SARIF_EXPORT)} " : '') .
            ($this->enableMarkdownExport ? "-markdown-export {$this->helpers->filesystem()->getPathFromElements($spTestResultsDir, self::DIR_MARKDOWN_EXPORT)} " : '') .
            ($this->enableDebug ? '-debug ' : '') .
            ($this->enableVerbose ? '-verbose ' : '') .
            "2>&1 " .
            "| sed 's/$token/hidden/g' " . // Remove token from output
            ($this->enableOutputExport ?  "| tee {$this->helpers->filesystem()->getPathFromElements($spTestResultsDir, self::FILE_OUTPUT_EXPORT)}; " : "; ")
        ;

        // Now use headless browser template to take the pictures only.
        $this->templateId = self::NUCLEI_TEMPLATE_SAML_HEADLESS_ALL;

        // Currently no result exports because of the false positive matches with headless browser.
        $command .=
            "nuclei -target $target " .
            "-env-vars -headless -matcher-status -follow-redirects -disable-update-check -timestamp " .
            "-templates {$this->getTemplatesPath()} " .
            "-var SP_ENTITY_ID=$spEntityId " .
            "-var CONSUMER_URL=$acsUrl " .
            "-var CONFORMANCE_IDP_BASE_URL=$this->conformanceIdpBaseUrl " .
            "-var CONFORMANCE_IDP_HOSTNAME=$this->conformanceIdpHostname " .
            "-var RESULT_OUTPUT_DIR=$spTestResultsDir " .
            "-var FILENAME=$fileName " .
            "-var TOKEN=$token " .
            ($testId ? "-var TEST_ID=$testId " : '') .
            ($this->enableDebug ? '-debug ' : '') .
            ($this->enableVerbose ? '-verbose ' : '') .
            "2>&1 " .
            "| sed 's/$token/hidden/g'; " // Remove token from output
        ;

        // Cleanup part of the tests.
        $command .=
            "find $spTestResultsDir -type f -exec sed -i 's/$token/hidden/g' {} +; " . # Remove token from exports
            // phpcs:ignore
            "find $spTestResultsDir -mindepth 1 -maxdepth 1 -type d -printf '%f\\n' | sort -n | head -n -$this->numberOfResultsToKeepPerSp | xargs -r -I '{}' rm -rf $spTestResultsDir/'{}'" # Limit number of results per SP
        ;

        return $command;
    }

    public function getSpResultsDir(string $spEntityId): string
    {
        return $this->dataDir . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR . hash('sha256', $spEntityId);
    }

    /**
     * @param string $spEntityId
     * @param string|null $testInstanceIdentifier Identifier for single test run. If not provided, current time is used.
     * @return string
     */
    public function getSpTestResultsDir(string $spEntityId, string $testInstanceIdentifier = null): string
    {
        return $this->getSpResultsDir($spEntityId) . DIRECTORY_SEPARATOR .
            ($testInstanceIdentifier ?? (new \DateTime())->getTimestamp());
    }

    /**
     * @throws ConformanceException
     */
    public function setTemplateId(string $templateId): NucleiEnv
    {
        if (
            file_exists($this->templatesDir . DIRECTORY_SEPARATOR . $templateId . self::KEY_TEMPLATE_EXTENSION)
        ) {
            $this->templateId = $templateId;
            return $this;
        }

        throw new ConformanceException('Invalid Nuclei template ID provided.');
    }

    public function getTemplatesPath(): string
    {
        return $this->templatesDir . DIRECTORY_SEPARATOR .
            ($this->templateId ? $this->templateId . self::KEY_TEMPLATE_EXTENSION : '');
    }
}
