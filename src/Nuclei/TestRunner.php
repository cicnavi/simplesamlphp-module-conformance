<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Nuclei;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultRepository;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Errors\SpMetadataException;
use SimpleSAML\Module\conformance\Errors\Tests\AcsUrlException;
use SimpleSAML\Module\conformance\Errors\Tests\ConsentException;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\SpConsentHandler;
use SimpleSAML\Module\conformance\SspBridge;
use Throwable;

class TestRunner
{
    protected const KEY_ASSERTION_CONSUMER_SERVICE = 'AssertionConsumerService';
    protected const KEY_LOCATION = 'Location';

    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected SpConsentHandler $spConsentHandler,
        protected MetaDataStorageHandler $metaDataStorageHandler,
        public readonly Env $env,
        protected Helpers $helpers,
        protected TestResultRepository $testResultRepository,
    ) {
    }

    /**
     * @throws ConformanceException
     */
    public function run(
        string $token,
        string $spEntityId,
        ?string $acsUrl = null,
        ?string $testId = null,
    ): string {
        $output = $this->execute($this->prepareCommand($token, $spEntityId, $acsUrl, $testId));

        if (!is_string($output)) {
            throw new ConformanceException('Unexpected command output encountered.');
        }

        $this->persistLatestResults($spEntityId);

        return $output;
    }

    /**
     * @throws SpMetadataException
     */
    public function resolveSpMetadataConfig(string $spEntityId): Configuration
    {
        try {
            return $this->metaDataStorageHandler->getMetaDataConfig(
                $spEntityId,
                SspBridge::KEY_SET_SP_REMOTE
            );
        } catch (Throwable $exception) {
            throw new SpMetadataException('Could not get metadata for ' . $spEntityId, 0, $exception);
        }
    }

    /**
     * @throws ConsentException
     */
    public function handleConsent(string $spEntityId, array $spMetadata): void
    {
        if (
            $this->spConsentHandler->shouldValidateConsentForSp($spEntityId) &&
            (! $this->spConsentHandler->isConsentedForSp($spEntityId))
        ) {
            $message = 'SP consent is required to run tests. ';
            if (! $this->spConsentHandler->isRequestedForSp($spEntityId)) {
                try {
                    $this->spConsentHandler->requestForSp($spEntityId, $spMetadata);
                    $message .= 'Request for consent has now been sent.';
                } catch (Throwable $exception) {
                    $message .= 'Error requesting consent: ' . $exception->getMessage();
                }
            } else {
                $message .= 'Consent has already been requested, but still not accepted.';
            }

            throw new ConsentException($message);
        }
    }

    /**
     * @throws AcsUrlException
     */
    public function resolveDefaultAcsUrl(Configuration $spMetadataConfig): string
    {
        try {
            /** @psalm-suppress MixedAssignment */
            $acsUrl = $spMetadataConfig->getDefaultEndpoint(self::KEY_ASSERTION_CONSUMER_SERVICE);

            if (is_array($acsUrl)) {
                $acsUrl = (string)($acsUrl[self::KEY_LOCATION] ?? '');
            } else {
                $acsUrl = (string)$acsUrl;
            }

            return $acsUrl;
        } catch (Exception) {
            throw new AcsUrlException('Unable to resolve default ACS URL.');
        }
    }

    /**
     * @throws ConsentException
     * @throws AcsUrlException
     * @throws SpMetadataException
     */
    public function prepareCommand(
        string $token,
        string $spEntityId,
        ?string $acsUrl = null,
        ?string $testId = null,
    ): string {
        $spMetadataConfig = $this->resolveSpMetadataConfig($spEntityId);

        // We have trusted SP. Handle consent if needed.
        $this->handleConsent($spEntityId, $spMetadataConfig->toArray());

        // Consent is ok, so we can move on.
        $acsUrl ??= $this->resolveDefaultAcsUrl($spMetadataConfig);

        return $this->env->prepareCommand($spEntityId, $acsUrl, $token, $testId);
    }

    public function execute(string $command): bool|string|null
    {
        /** @psalm-suppress ForbiddenCode */
        return shell_exec($command);
    }

    public function persistLatestResults(string $spEntityId): void
    {
        // Find all artifacts that Nuclei produced, parse them and store results in DB.
        $this->env->getSpTestResultsDir($spEntityId);

        $files = [];
        if (file_exists($resultsDir = $this->env->getSpResultsDir($spEntityId))) {
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

        $latestTimestamp = key($artifacts);
        $latestArtifacts = current($artifacts);
        if (
            (!is_null($latestTimestamp)) &&
            is_array($latestArtifacts)
        ) {
            $jsonResult = null;
            if (
                in_array(Env::FILE_JSON_EXPORT, $latestArtifacts) &&
                file_exists(
                    $jsonResultPath = $this->helpers->filesystem()->getPathFromElements(
                        $this->env->getSpResultsDir($spEntityId),
                        strval($latestTimestamp),
                        Env::FILE_JSON_EXPORT
                    )
                ) &&
                $jsonResultContent = file_get_contents($jsonResultPath)
            ) {
                $jsonResult = $jsonResultContent;
            }

            $findings = null;
            if (
                in_array(Env::FILE_FINDINGS_EXPORT, $latestArtifacts) &&
                file_exists(
                    $findingsPath = $this->helpers->filesystem()->getPathFromElements(
                        $this->env->getSpResultsDir($spEntityId),
                        strval($latestTimestamp),
                        Env::FILE_FINDINGS_EXPORT
                    )
                ) &&
                $findingsContent = file_get_contents($findingsPath)
            ) {
                $findings = $findingsContent;
            }

            $this->testResultRepository->addForSp(
                $spEntityId,
                intval($latestTimestamp),
                $jsonResult,
                $findings,
            );
            $this->testResultRepository->deleteObsolete(
                $spEntityId,
                $this->moduleConfiguration->getNumberOfResultsToKeepPerSp()
            );
        }
    }
}
