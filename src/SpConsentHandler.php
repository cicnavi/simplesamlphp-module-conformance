<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use SimpleSAML\Configuration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Controllers\SpConsent;
use SimpleSAML\Module\conformance\Database\Repositories\SpConsentRequestRepository;
use SimpleSAML\Module\conformance\Database\Repositories\SpConsentRepository;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Errors\SpConsentException;
use SimpleSAML\Module\conformance\Factories\EmailFactory;
use SimpleSAML\Module\conformance\Helpers\Routes;
use Throwable;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class SpConsentHandler
{
    final public const KEY_METADATA_CONTACTS = 'contacts';
    final public const KEY_METADATA_CONTACT_EMAIL_ADDRESS = 'emailAddress';

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected SpConsentRepository $spConsentRepository,
        protected SpConsentRequestRepository $spConsentRequestsRepository,
        protected Helpers $helpers,
        protected EmailFactory $emailFactory,
    ) {
    }

    public function shouldValidateConsentForSp(string $spEntityId): bool
    {
        return ($this->moduleConfiguration->shouldAcquireSpConsentBeforeTests()) &&
            (!in_array($spEntityId, $this->moduleConfiguration->getSpsWIthOverriddenConsents()));
    }

    public function isConsentedForSp(string $spEntityId): bool
    {
        return ! is_null($this->getSpConsent($spEntityId));
    }

    public function getSpConsent(string $spEntityId): ?array
    {
        return $this->spConsentRepository->get($spEntityId);
    }

    public function isRequestedForSp(string $spEntityId): bool
    {
        return ! is_null($this->spConsentRequestsRepository->get($spEntityId));
    }

    /**
     * @throws SpConsentException
     * @throws ConformanceException
     */
    public function requestForSp(string $spEntityId, array $spMetadata): void
    {
        $challenge = $this->spConsentRequestsRepository->generate($spEntityId);

        $contactEmails = $this->resolveProviderContactEmails($spMetadata);

        if (empty($contactEmails)) {
            throw new SpConsentException('No contact emails available for SP ' . $spEntityId);
        }

        $challengeUrl = $this->helpers->routes()->getUrl(
            Routes::PATH_SP_CONSENTS_VERIFY_CHALLENGE,
            ModuleConfiguration::MODULE_NAME,
            [Conformance::KEY_SP_ENTITY_ID => $spEntityId, SpConsent::KEY_CHALLENGE => $challenge]
        );

        $failedEmails = [];
        $errors = [];
        foreach ($contactEmails as $contactEmail) {
            try {
                $emailInstance = $this->emailFactory->build(
                    Translate::noop('Conformance test consent'),
                    null,
                    $contactEmail,
                    ModuleConfiguration::MODULE_NAME . ':emails/sp-consent/mailtxt.twig',
                    ModuleConfiguration::MODULE_NAME . ':emails/sp-consent/mailhtml.twig',
                );

                $emailInstance->setText(
                    Translate::noop(
                        'You have been asked for consent to run conformance tests against ' .
                        ' Service Provider noted below. To accept the invitation, click (or navigate to) the given URL.'
                    )
                );

                $emailInstance->setData([
                    'spEntityId' => $spEntityId,
                    'challengeUrl' => $challengeUrl,
                ]);

                $emailInstance->send();
            } catch (Throwable $exception) {
                $failedEmails[] = $contactEmail;
                $errors[] = $contactEmail . ": {$exception->getMessage()}";
            }
        }

        // If all emails have failed
        if (empty(array_diff($contactEmails, $failedEmails))) {
            $this->spConsentRequestsRepository->delete($spEntityId);
            throw new SpConsentException(
                'Could not send email to any contact for SP ' . $spEntityId .
                " Errors were: " . implode('; ', $errors)
            );
        }
    }

    public function getSpConsentRequest(string $spEntityId): ?array
    {
        return $this->spConsentRequestsRepository->get($spEntityId);
    }

    public function getSpConsents(): array
    {
        return $this->spConsentRepository->getAll();
    }

    public function getSpConsentRequests(): array
    {
        return $this->spConsentRequestsRepository->getAll();
    }

    public function addConsent(string $spEntityId): void
    {
        $this->spConsentRepository->add($spEntityId);
        $this->spConsentRequestsRepository->delete($spEntityId);
    }

    /**
     * @return string[]
     */
    protected function resolveProviderContactEmails(array $spMetadata): array
    {
        $emails = [];

        if (
            (! isset($spMetadata[self::KEY_METADATA_CONTACTS])) ||
            (! is_array($spMetadata[self::KEY_METADATA_CONTACTS])) ||
            (empty($spMetadata[self::KEY_METADATA_CONTACTS]))
        ) {
                return $emails;
        }

        foreach ($spMetadata[self::KEY_METADATA_CONTACTS] as $contact) {
            if (
                (! is_array($contact)) ||
                (! isset($contact[self::KEY_METADATA_CONTACT_EMAIL_ADDRESS]))
            ) {
                continue;
            }

            if (is_array($contact[self::KEY_METADATA_CONTACT_EMAIL_ADDRESS])) {
                /** @psalm-suppress MixedAssignment */
                foreach ($contact[self::KEY_METADATA_CONTACT_EMAIL_ADDRESS] as $email) {
                    if (
                        (! is_string($email)) ||
                        (! $this->isValidEmail($email))
                    ) {
                        continue;
                    }

                    $emails[] = $email;
                }
            }

            if (
                is_string($contact[self::KEY_METADATA_CONTACT_EMAIL_ADDRESS]) &&
                $this->isValidEmail($contact[self::KEY_METADATA_CONTACT_EMAIL_ADDRESS])
            ) {
                $emails[] = $contact[self::KEY_METADATA_CONTACT_EMAIL_ADDRESS];
            }
        }

        return array_unique(array_map(function ($email) {
            return str_replace('mailto:', '', $email);
        }, $emails));
    }

    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
