<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Database\Repositories\SpConsentRequestRepository;
use SimpleSAML\Module\conformance\Factories\EmailFactory;
use SimpleSAML\Module\conformance\Factories\GenericStatusFactory;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\SpConsentHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function noop;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class SpConsent
{
    final public const KEY_CHALLENGE = 'challenge';
    final public const KEY_CONTACT_EMAIL = 'contact_email';

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected TemplateFactory $templateFactory,
        protected Authorization $authorization,
        protected SpConsentHandler $spConsentHandler,
        protected GenericStatusFactory $genericStatusFactory,
        protected EmailFactory $emailFactory,
    ) {
    }

    public function index(): Response
    {
        $this->authorization->requireSimpleSAMLphpAdmin(true);

        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':sp-consents/index.twig',
            Routes::PATH_SP_CONSENTS_INDEX,
        );

        $consented = [];
        $requested = [];
        $overridden = [];

        $isConsentRequired = $this->moduleConfiguration->shouldAcquireSpConsentBeforeTests();

        if ($isConsentRequired) {
            $consented = $this->spConsentHandler->getSpConsents();
            $requested = $this->spConsentHandler->getSpConsentRequests();
            $overridden = $this->moduleConfiguration->getSpsWIthOverriddenConsents();
        }

        $template->data += [
            'isConsentRequired' => $isConsentRequired,
            'consented' => $consented,
            'requested' => $requested,
            'overridden' => $overridden,
        ];

        return $template;
    }
    public function verifyChallenge(Request $request): Response
    {
        // No authorization needed.
        $spEntityId = $request->query->get(Conformance::KEY_SP_ENTITY_ID);
        $spEntityId = $spEntityId ? (string)$spEntityId : null;
        $challenge = $request->query->get(self::KEY_CHALLENGE);
        $challenge = $challenge ? (string)$challenge : null;
        $contactEmail = $request->query->get(self::KEY_CONTACT_EMAIL);
        $contactEmail = $contactEmail ? (string)$contactEmail : null;

        $status = $this->genericStatusFactory->fromRequest($request);

        if (empty($spEntityId) || empty($challenge) || empty($contactEmail)) {
            $status->setStatusError()->setMessage(noop('Missing required parameters.'));
        } elseif (! $this->spConsentHandler->shouldValidateConsentForSp($spEntityId)) {
            $status->setStatusError()->setMessage(noop('SP consent not required.'));
        } elseif ($this->spConsentHandler->isConsentedForSp($spEntityId)) {
            $status->setStatusError()->setMessage(noop('SP consent already acquired.'));
        } else {
            $consentRequest = $this->spConsentHandler->getSpConsentRequest($spEntityId, $contactEmail);
            if (is_null($consentRequest)) {
                $status->setStatusError()->setMessage(noop('SP consent not requested.'));
            } elseif (
                (isset($consentRequest[SpConsentRequestRepository::COLUMN_CHALLENGE]) &&
                    (string)$consentRequest[SpConsentRequestRepository::COLUMN_CHALLENGE] === $challenge) &&
                (isset($consentRequest[SpConsentRequestRepository::COLUMN_CONTACT_EMAIL]) &&
                    (string)$consentRequest[SpConsentRequestRepository::COLUMN_CONTACT_EMAIL] === $contactEmail)
            ) {
                $this->spConsentHandler->addConsent($spEntityId, $contactEmail);
                $status->setStatusOk()->setMessage(noop('SP consent added.'));
            } else {
                $status->setStatusError()->setMessage(noop('Could not verify challenge.'));
            }
        }

        $this->templateFactory->setShowMenu(false);
        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':sp-consents/verify-challenge.twig',
            Routes::PATH_SP_CONSENTS_INDEX,
            $status
        );

        return $template;
    }
}
