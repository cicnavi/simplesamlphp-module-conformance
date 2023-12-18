<?php

namespace SimpleSAML\Test\Module\conformance;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Errors\InvalidConfigurationException;
use SimpleSAML\Module\conformance\ModuleConfig;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\conformance\ModuleConfig
 */
class ModuleConfigTest extends TestCase
{
    protected array $configOverrides = [];

    protected function setUp(): void
    {
    }

    /**
     * @throws Exception
     */
    protected function mocked(string $fileName = null, array $configOverrides = null): ModuleConfig
    {
        $configOverrides ??= $this->configOverrides;
        return new ModuleConfig($fileName, $configOverrides);
    }

    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(ModuleConfig::class, $this->mocked());
    }

    public function testCanGetOptions(): void
    {
        $moduleConfig = $this->mocked();
        $this->assertInstanceOf(Configuration::class, $moduleConfig->getConfig());

        $this->assertSame('dummy.key', $moduleConfig->get(ModuleConfig::OPTION_DUMMY_PRIVATE_KEY));
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
