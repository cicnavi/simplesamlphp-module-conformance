<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\BulkTest\State;
use SimpleSAML\Module\conformance\Database\Repositories\SpConsentRepository;
use SimpleSAML\Module\conformance\Factories\BulkTestStateFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\NucleiEnv;
use SimpleSAML\Module\conformance\SpConsentHandler;
use SimpleSAML\Module\conformance\SspBridge;
use Symfony\Component\HttpFoundation\Response;

class Runner
{
    protected const KEY_ASSERTION_CONSUMER_SERVICE = 'AssertionConsumerService';
    protected const KEY_LOCATION = 'Location';
    protected int $id;

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected Helpers $helpers,
        protected BulkTestStateFactory $bulkTestStateFactory,
        protected MetaDataStorageHandler $metaDataStorageHandler,
        protected SpConsentRepository $spConsentRepository,
        protected SpConsentHandler $spConsentHandler,
        protected NucleiEnv $nucleiEnv,
        protected LoggerInterface $logger,
    ) {
        $this->id = $this->helpers->random()->int();
    }

    public function run(): Response
    {
        // TODO mivanci keep run state in cache
        $state = $this->bulkTestStateFactory->build($this->id);
        $spEntityIds = $this->metaDataStorageHandler->getList(SspBridge::KEY_SET_SP_REMOTE);

        foreach ($spEntityIds as $spEntityId => $spMetadataArray) {

            // TODO mivanci extract as this is the same code as in NucleiTest controller
            // We have trusted SP. Handle consent if needed.
            if (
                $this->spConsentHandler->shouldValidateConsentForSp($spEntityId) &&
                (! $this->spConsentHandler->isConsentedForSp($spEntityId))
            ) {
                $message = 'SP consent is required to run tests. ';
                if (! $this->spConsentHandler->isRequestedForSp($spEntityId)) {
                    try {
                        $this->spConsentHandler->requestForSp($spEntityId, $spMetadataArray);
                        $message .= 'Request for consent has now been sent.';
                    } catch (\Throwable $exception) {
                        $message .= 'Error requesting consent: ' . $exception->getMessage();
                    }
                } else {
                    $message .= 'Consent has already been requested, but still not accepted.';
                }

                $state->incrementFailedJobsProcessed();
                $state->addStatusMessage("Consent for SP $spEntityId: " . $message);
                continue;
            }

            $state->addStatusMessage("Starting with tests for: $spEntityId");

            $spMetadataConfig = $this->metaDataStorageHandler->getMetaDataConfig($spEntityId, SspBridge::KEY_SET_SP_REMOTE);

            // TODO mivanci extract as this is the same code as in NucleiTest controller
            try {
                /** @psalm-suppress MixedAssignment */
                $acsUrl = $spMetadataConfig->getDefaultEndpoint(self::KEY_ASSERTION_CONSUMER_SERVICE);
            } catch (\Throwable $exception) {
                $state->addStatusMessage("ACS URL for SP $spEntityId: Could not resolve Assertion Consumer Service (ACS).");
                continue;
            }

            if (is_array($acsUrl)) {
                $acsUrl = (string)($acsUrl[self::KEY_LOCATION] ?? '');
            } else {
                $acsUrl = (string)$acsUrl;
            }

            $token = $this->moduleConfiguration->getLocalTestRunnerToken();

            $command = $this->nucleiEnv->prepareCommand($spEntityId, $acsUrl, $token);

            `$command`;

            $state->addStatusMessage("Finished for $spEntityId");
        }

        return new Response(var_dump($state->getStatusMessages(), true));

        return $state;
    }
}