<?php

declare(strict_types=1);

use SimpleSAML\Module\conformance\ModuleConfig;

$config = [
    /**
     * Private key which will be used to create invalid signatures in SAML Responses.
     */
    ModuleConfig::OPTION_DUMMY_PRIVATE_KEY => 'dummy.key',

    /**
     * Optional base URL for Conformance IdP which will used when generating SSO service URLs.
     * If not set, it will be resolved automatically based on the currently set host and/or SimpleSAMLphp configuration.
     * Example: 'https://conformance-idp.maiv1.incubator.geant.org'
     */
    ModuleConfig::OPTION_CONFORMANCE_IDP_BASE_URL => null,
];
