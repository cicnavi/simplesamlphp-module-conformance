<?php

namespace SimpleSAML\Test\Module\conformance;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Errors\InvalidConfigurationException;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\conformance\ModuleConfiguration
 */
class ModuleConfigurationTest extends TestCase
{
    protected array $configOverrides = [];

    protected function setUp(): void
    {
    }

    /**
     * @throws Exception
     */
    protected function mocked(string $fileName = null, array $configOverrides = null): ModuleConfiguration
    {
        $configOverrides ??= $this->configOverrides;
        return new ModuleConfiguration($fileName, $configOverrides);
    }

    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(ModuleConfiguration::class, $this->mocked());
    }

    public function testCanGetOptions(): void
    {
        $moduleConfig = $this->mocked();
        $this->assertInstanceOf(Configuration::class, $moduleConfig->getConfig());

        $this->assertSame('dummy.key', $moduleConfig->get(ModuleConfiguration::OPTION_DUMMY_PRIVATE_KEY));
        $this->assertSame('dummy.key', $moduleConfig->getDummyPrivateKey());
        $this->assertStringEndsWith('src', $moduleConfig->getModuleSourceDirectory());
        $this->assertStringEndsWith('conformance', $moduleConfig->getModuleRootDirectory());
    }

    public function testThrowsForNonExistingOption(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->mocked()->get('invalid');
    }
}
