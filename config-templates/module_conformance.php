<?php

declare(strict_types=1);

use SimpleSAML\Module\conformance\ModuleConfiguration;

$config = [
    /**
     * Private key which will be used to create invalid signatures in SAML Responses.
     */
    ModuleConfiguration::OPTION_DUMMY_PRIVATE_KEY => 'dummy.key',

    /**
     * Optional base URL for Conformance IdP which will used when generating SSO service URLs.
     * If not set, it will be resolved automatically based on the currently set host and/or SimpleSAMLphp configuration.
     * Example: 'https://conformance-idp.maiv1.incubator.geant.org'
     */
    ModuleConfiguration::OPTION_CONFORMANCE_IDP_BASE_URL => null,

    /**
     * Number of Nuclei results to keep per Service Provider (SP).
     */
    ModuleConfiguration::OPTION_NUMBER_OF_RESULTS_TO_KEEP_PER_SP => 10,


];
