<?php

declare(strict_types=1);

use SimpleSAML\Module\conformance\ModuleConfiguration;

$config = [
    /**
     * Private key which will be used to create invalid signatures in SAML Responses.
     */
    ModuleConfiguration::OPTION_DUMMY_PRIVATE_KEY => 'dummy.key',

    /**
     * Token which will be used when running tests locally, for example, from user interface.
     * This token has the same access level as SimpleSAMLphp administrator.
     * Must be set to strong random token string.
     *
     * The format is: 'strong-random-token-string',
     */
    ModuleConfiguration::OPTION_LOCAL_TEST_RUNNER_TOKEN => null,

    /**
     * List of administrative access tokens which can be used to access the whole conformance API.
     * These tokens have the same access level as SimpleSAMLphp administrator, and are typically intended for
     * federation operators and their automation tools.
     *
     * The format is: ['token' => 'description',],
     */
    ModuleConfiguration::OPTION_ADMINISTRATIVE_TOKENS => [
        //'strong-random-token-string' => 'Token description',
    ],

    /**
     * List of access tokens limited to particular Service Providers (SPs). These tokens can be used in scenarios
     * in which it is necessary to limit conformance API access to only particular SPs.
     *
     * The format is: ['token' => ['sp-entity-id', 'sp-entity-id-2',],]
     */
    ModuleConfiguration::OPTION_SERVICE_PROVIDER_TOKENS => [
        //'strong-random-token-string' => ['sp-entity-id', 'sp-entity-id-2',],
    ],

    /**
     * Number of Nuclei results to keep per Service Provider (SP).
     */
    ModuleConfiguration::OPTION_NUMBER_OF_RESULTS_TO_KEEP_PER_SP => 10,

    /**
     * Prefix to apply to DB tables names related to the conformance module.
     */
    ModuleConfiguration::OPTION_DATABASE_TABLE_NAMES_PREFIX => 'cnfrmnc_',

    /**
     * Choose if the consent should be acquired before any tests are run for all service providers.
     * If true, before the test, the test runner will check if the consent is acquired for particular SP.
     * If not, it will aks for a consent by sending an email with a consent challenge to the service provider contacts.
     */
    ModuleConfiguration::OPTION_SHOULD_ACQUIRE_SP_CONSENT_BEFORE_TESTS => true,

    /**
     * If you want to run tests for particular service providers without requesting them for consent, note it here.
     */
    ModuleConfiguration::OPTION_SPS_WITH_OVERRIDDEN_CONSENTS => [
        // 'sp-entity-id',
    ],

];
