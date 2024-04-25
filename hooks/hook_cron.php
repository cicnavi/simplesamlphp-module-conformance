<?php

declare(strict_types=1);

use SAML2\Compat\Ssp\Logger;
use SimpleSAML\Configuration;
use SimpleSAML\Database;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Database\Repositories\SpConsentRepository;
use SimpleSAML\Module\conformance\Database\Repositories\SpConsentRequestRepository;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultImageRepository;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultRepository;
use SimpleSAML\Module\conformance\Factories\BulkTestStateFactory;
use SimpleSAML\Module\conformance\Factories\EmailFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\Nuclei\BulkTest;
use SimpleSAML\Module\conformance\Nuclei\Env;
use SimpleSAML\Module\conformance\Nuclei\TestRunner;
use SimpleSAML\Module\conformance\SpConsentHandler;
use SimpleSAML\Module\conformance\SspBridge;

function conformance_hook_cron(array &$cronInfo): void
{
    $sspConfiguration = Configuration::getInstance();
    $moduleConfiguration = new ModuleConfiguration();
    $helpers = new Helpers();
    $bulkTestStateFactory = new BulkTestStateFactory();
    $metaDataStorageHandler = MetaDataStorageHandler::getMetadataHandler();
    $database = Database::getInstance();
    $spConsentRepository = new SpConsentRepository(
        $sspConfiguration,
        $moduleConfiguration,
        $database,
        $helpers,
    );
    $spConsentRequestRepository = new SpConsentRequestRepository(
        $sspConfiguration,
        $moduleConfiguration,
        $database,
        $helpers,
    );
    $emailFactory = new EmailFactory();
    $spConsentHandler = new SpConsentHandler(
        $sspConfiguration,
        $moduleConfiguration,
        $spConsentRepository,
        $spConsentRequestRepository,
        $helpers,
        $emailFactory,
    );
    $sspBridge = new SspBridge();
    $nucleiEnv = new Env(
        $sspConfiguration,
        $moduleConfiguration,
        $helpers,
        $sspBridge,
    );
    $testResultRepository = new TestResultRepository(
        $sspConfiguration,
        $moduleConfiguration,
        $database,
        $helpers,
    );
    $testResultImageRepository = new TestResultImageRepository(
        $sspConfiguration,
        $moduleConfiguration,
        $database,
        $helpers,
    );
    $nucleiTestRunner = new TestRunner(
        $moduleConfiguration,
        $spConsentHandler,
        $metaDataStorageHandler,
        $nucleiEnv,
        $helpers,
        $testResultRepository,
        $testResultImageRepository,
    );
    $logger = new Logger();

    /** @var ?string $currentCronTag */
    $currentCronTag = $cronInfo['tag'] ?? null;

    if (!is_array($cronInfo['summary'])) {
        $cronInfo['summary'] = [];
    }

    /**
     * Test runner handling.
     */
    $cronTagForBulkTestRunner = $moduleConfiguration->getCronTagForBulkTestRunner();
    try {
        if ($currentCronTag === $cronTagForBulkTestRunner) {
            $state = (new BulkTest(
                $sspConfiguration,
                $moduleConfiguration,
                $helpers,
                $bulkTestStateFactory,
                $metaDataStorageHandler,
                $nucleiTestRunner,
                $logger,
            ))->run();
            foreach ($state->getStatusMessages() as $statusMessage) {
                $cronInfo['summary'][] = $statusMessage;
            }
            $message = sprintf(
                'Test processing finished with %s successful tests, %s failed tests; total: %s.',
                $state->getSuccessfulTestsProcessed(),
                $state->getFailedTestsProcessed(),
                $state->getTotalTestsProcessed()
            );
            $cronInfo['summary'][] = $message;
        }
    } catch (Throwable $exception) {
        $message = 'Test runner error: ' . $exception->getMessage();
        /** @psalm-suppress MixedArrayAssignment */
        $cronInfo['summary'][] = $message;
    }
}
