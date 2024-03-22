<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Errors\InvalidConfigurationException;

class ModuleConfiguration
{
    final public const MODULE_NAME = 'conformance';
    final public const KEY_DATADIR = 'datadir';

    /**
     * Default file name for module configuration. Can be overridden in constructor, for example, for testing purposes.
     */
    final public const FILE_NAME = 'module_conformance.php';
    final public const OPTION_DUMMY_PRIVATE_KEY = 'dummy-private-key';
    final public const OPTION_CONFORMANCE_IDP_BASE_URL = 'conformance-idp-base-url';
    final public const OPTION_NUMBER_OF_RESULTS_TO_KEEP_PER_SP = 'number-of-results-to-keep-per-sp';
    final public const OPTION_ADMINISTRATIVE_TOKENS = 'administrative-tokens';
    final public const OPTION_SERVICE_PROVIDER_TOKENS = 'service-provider-tokens';
    final public const OPTION_LOCAL_TEST_RUNNER_TOKEN = 'local-test-runner-token';
    final public const OPTION_DATABASE_TABLE_NAMES_PREFIX = 'database-table-name-prefix';
    final public const OPTION_SHOULD_ACQUIRE_SP_CONSENT_BEFORE_TESTS = 'should-acquire-sp-consent-before-tests';
    final public const OPTION_SPS_WITH_OVERRIDDEN_CONSENTS = 'sps-with-overridden-consents';
    final public const OPTION_SP_CONSENT_CHALLENGE_TTL = 'sp-consent-challenge-ttl';
    final public const OPTION_CRON_TAG_FOR_BULK_TEST_RUNNER = 'cron_tag_for_bulk_test_runner';

    /**
     * Contains configuration from module configuration file.
     */
    protected Configuration $config;

    /**
     * @throws Exception
     */
    public function __construct(string $fileName = null, array $overrides = [])
    {
        $fileName ??= self::FILE_NAME;

        $fullConfigArray = array_merge(Configuration::getConfig($fileName)->toArray(), $overrides);

        $this->config = Configuration::loadFromArray($fullConfigArray);
    }

    /**
     * Get underlying SimpleSAMLphp Configuration instance for the module.
     *
     * @return Configuration
     */
    public function getConfig(): Configuration
    {
        return $this->config;
    }

    /**
     * Get configuration option from module configuration file.
     * @throws InvalidConfigurationException
     */
    public function get(string $option): mixed
    {
        if (!$this->config->hasValue($option)) {
            throw new InvalidConfigurationException(
                sprintf('Configuration option does not exist (%s).', $option)
            );
        }

        return $this->config->getValue($option);
    }

    public function getModuleSourceDirectory(): string
    {
        return __DIR__;
    }

    public function getModuleRootDirectory(): string
    {
        return dirname(__DIR__);
    }

    public function getDummyPrivateKey(): string
    {
        return $this->getConfig()->getString(self::OPTION_DUMMY_PRIVATE_KEY);
    }

    public function getConformanceIdpBaseUrl(): ?string
    {
        return $this->getConfig()->getOptionalString(self::OPTION_CONFORMANCE_IDP_BASE_URL, null);
    }

    public function getLocalTestRunnerToken(): string
    {
        return $this->getConfig()->getString(self::OPTION_LOCAL_TEST_RUNNER_TOKEN);
    }

    public function getAdministrativeTokens(): array
    {
        return $this->getConfig()->getOptionalArray(self::OPTION_ADMINISTRATIVE_TOKENS, null) ?? [];
    }

    public function getServiceProviderTokens(): array
    {
        return $this->getConfig()->getOptionalArray(self::OPTION_SERVICE_PROVIDER_TOKENS, null) ?? [];
    }

    public function hasLocalTestRunnerToken(string $token): bool
    {
        return $token === $this->getLocalTestRunnerToken();
    }

    public function hasAdministrativeToken(string $token): bool
    {
        return array_key_exists($token, $this->getAdministrativeTokens());
    }

    public function hasServiceProviderToken(string $token, string $spEntityId): bool
    {
        $tokens = $this->getServiceProviderTokens();

        if (! array_key_exists($token, $tokens)) {
            return false;
        }

        if (
            (! is_array($tokenSps = $tokens[$token])) ||
            (! in_array($spEntityId, $tokenSps))
        ) {
            return false;
        }

        return true;
    }

    public function getNumberOfResultsToKeepPerSp(): int
    {
        return $this->getConfig()->getIntegerRange(self::OPTION_NUMBER_OF_RESULTS_TO_KEEP_PER_SP, 1, 1000);
    }

    public function getDatabaseTableNamesPrefix(): string
    {
        return $this->getConfig()->getOptionalString(self::OPTION_DATABASE_TABLE_NAMES_PREFIX, 'cnfrmnc_');
    }

    public function shouldAcquireSpConsentBeforeTests(): bool
    {
        return $this->getConfig()->getOptionalBoolean(self::OPTION_SHOULD_ACQUIRE_SP_CONSENT_BEFORE_TESTS, true);
    }

    public function getSpsWIthOverriddenConsents(): array
    {
        return $this->getConfig()->getOptionalArray(self::OPTION_SPS_WITH_OVERRIDDEN_CONSENTS, []);
    }

    public function getSpConsentChallengeTimeToLive(): int
    {
        $defaultTtl = 48 * 60 * 60;
        return $this->getConfig()->getOptionalIntegerRange(
            self::OPTION_SP_CONSENT_CHALLENGE_TTL,
            60,
            $defaultTtl,
            $defaultTtl
        );
    }

    public function getCronTagForBulkTestRunner()
    {

    }
}
