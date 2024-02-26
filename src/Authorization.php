<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use SimpleSAML\Error\Exception;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\conformance\Errors\AuthorizationException;
use SimpleSAML\Module\conformance\SspBridge\Utils;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * @psalm-suppress InternalMethod
 */
class Authorization
{
    public const KEY_TOKEN = 'token';
    public const KEY_AUTHORIZATION = 'Authorization';

    public const KEY_SP_ENTITY_ID = 'serviceProviderEntityId';

    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected Utils $utils,
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function requireSimpleSAMLphpAdmin(bool $forceAdminAuthentication = false): void
    {
        if ($forceAdminAuthentication) {
            try {
                $this->utils->auth()->requireAdmin();
            } catch (Exception $exception) {
                throw new AuthorizationException(
                    Translate::noop('Unable to initiate admin authentication.'),
                    $exception->getCode(),
                    $exception
                );
            }
        }

        if (! $this->utils->auth()->isAdmin()) {
            throw new AuthorizationException(Translate::noop('SimpleSAMLphp Admin access required.'));
        }
    }

    /**
     * Require a valid local test runner token. Local test runner token can perform actions as SimpleSAMLphp admin.
     *
     * @throws AuthorizationException
     */
    public function requireLocalTestRunnerToken(Request $request): void
    {
        try {
            $this->requireSimpleSAMLphpAdmin();
            return;
        } catch (Throwable) {
            // Not admin, check for local test runner token.
        }

        if (empty($token = $this->findToken($request))) {
            throw new AuthorizationException(Translate::noop('Token not provided.'));
        }

        if (! $this->moduleConfiguration->hasLocalTestRunnerToken($token)) {
            throw new AuthorizationException(Translate::noop('Local test runner token not valid.'));
        }
    }

    /**
     * Require a valid administrative token to be set. Administrative token can perform actions as SimpleSAMLphp admin.
     * @throws AuthorizationException
     */
    public function requireAdministrativeToken(Request $request): void
    {
        try {
            $this->requireLocalTestRunnerToken($request);
            return;
        } catch (Throwable) {
            // Not local test runner token, check for administrative token.
        }

        if (empty($token = $this->findToken($request))) {
            throw new AuthorizationException(Translate::noop('Token not provided.'));
        }

        if (! $this->moduleConfiguration->hasAdministrativeToken($token)) {
            throw new AuthorizationException(Translate::noop('Administrative token not valid.'));
        }
    }

    /**
     * Require a valid Service Provider (SP) token to be set. Token is only valid for actions for the given SP.
     * @throws AuthorizationException
     */
    public function requireServiceProviderToken(Request $request, string $spEntityId = null): void
    {
        try {
            $this->requireAdministrativeToken($request);
            return;
        } catch (Throwable) {
            // Not administrative token, check for service provider token.
        }

        $spEntityId ??= $this->findSpEntityId($request);

        if (empty($spEntityId)) {
            throw new AuthorizationException(Translate::noop('Service provider entity ID not provided.'));
        }

        if (empty($token = $this->findToken($request))) {
            throw new AuthorizationException(Translate::noop('Token not provided.'));
        }

        if (! $this->moduleConfiguration->hasServiceProviderToken($token, $spEntityId)) {
            throw new AuthorizationException(Translate::noop('Service provider token not valid.'));
        }
    }

    protected function findToken(Request $request): ?string
    {
        if ($token = trim((string) $request->get(self::KEY_TOKEN))) {
            return $token;
        }

        if ($request->headers->has(self::KEY_AUTHORIZATION)) {
            return trim(
                (string) preg_replace(
                    '/^\s*Bearer\s/',
                    '',
                    (string)$request->headers->get(self::KEY_AUTHORIZATION)
                )
            );
        }

        return null;
    }

    protected function findSpEntityId(Request $request): ?string
    {
        if ($spEntityId = trim((string) $request->get(self::KEY_SP_ENTITY_ID))) {
            return $spEntityId;
        }

        return null;
    }
}
