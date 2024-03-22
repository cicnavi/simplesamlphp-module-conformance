<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use SAML2\Compat\Ssp\Logger;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\ModuleConfiguration;

function conformance_hook_cron(array &$cronInfo): void
{
    $moduleConfiguration = new ModuleConfiguration();
    $logger = new Logger();

    /** @var ?string $currentCronTag */
    $currentCronTag = $cronInfo['tag'] ?? null;

    if (!is_array($cronInfo['summary'])) {
        $cronInfo['summary'] = [];
    }

    /**
     * Job runner handling.
     */
    $cronTagForJobRunner = $moduleConfiguration->getCronTagForBulkTestRunner();
    try {
        if ($currentCronTag === $cronTagForJobRunner) {
            $state = (new JobRunner($moduleConfiguration, Configuration::getConfig()))->run();
            foreach ($state->getStatusMessages() as $statusMessage) {
                $cronInfo['summary'][] = $statusMessage;
            }
            $message = sprintf(
                'Job processing finished with %s successful jobs, %s failed jobs; total: %s.',
                $state->getSuccessfulJobsProcessed(),
                $state->getFailedJobsProcessed(),
                $state->getTotalJobsProcessed()
            );
            $cronInfo['summary'][] = $message;
        }
    } catch (Throwable $exception) {
        $message = 'Job runner error: ' . $exception->getMessage();
        /** @psalm-suppress MixedArrayAssignment */
        $cronInfo['summary'][] = $message;
    }
}
