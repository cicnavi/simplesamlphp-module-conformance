<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting;

use SimpleSAML\Configuration;

class ModuleConfig
{
    public const MODULE_NAME = 'conformance';

    /**
     * Default file name for module configuration. Can be overridden in constructor, for example, for testing purposes.
     */
    public const FILE_NAME = 'module_conformance.php';

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

        $this->validate();
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

    /**
     * @throws InvalidConfigurationException
     */
    protected function validate(): void
    {
        $errors = [];

        if (!empty($errors)) {
            $message = sprintf('Module configuration validation failed with errors: %s', implode(' ', $errors));
            throw new InvalidConfigurationException($message);
        }
    }
}
