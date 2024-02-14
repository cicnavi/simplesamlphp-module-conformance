<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Errors\InvalidConfigurationException;

class ModuleConfig
{
    final public const MODULE_NAME = 'conformance';

    /**
     * Default file name for module configuration. Can be overridden in constructor, for example, for testing purposes.
     */
    final public const FILE_NAME = 'module_conformance.php';
    final public const OPTION_DUMMY_PRIVATE_KEY = 'dummy-private-key';
    final public const OPTION_CONFORMANCE_IDP_BASE_URL = 'conformance-idp-base-url';

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
}
