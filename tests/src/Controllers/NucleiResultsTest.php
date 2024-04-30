<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Controllers\NucleiResults;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultImageRepository;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultRepository;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use SimpleSAML\Module\conformance\Factories\TestResultFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\Nuclei\Env;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(NucleiResults::class)]
class NucleiResultsTest extends TestCase
{
    protected MockObject $sspConfigMock;
    protected MockObject $moduleConfigurationMock;
    protected MockObject $templateFactoryMock;
    protected MockObject $authorizationMock;
    protected MockObject $metaDataStorageHandlerMock;
    protected MockObject $nucleiEnvMock;
    protected MockObject $helpersMock;
    protected MockObject $loggerMock;
    protected MockObject $testResultRepositoryMock;
    protected MockObject $testResultFactoryMock;
    protected MockObject $testResultImageRepositoryMock;
    protected MockObject $requestMock;

    protected function setUp(): void
    {
        $this->sspConfigMock = $this->createMock(Configuration::class);
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
        $this->templateFactoryMock = $this->createMock(TemplateFactory::class);
        $this->authorizationMock = $this->createMock(Authorization::class);
        $this->metaDataStorageHandlerMock = $this->createMock(MetaDataStorageHandler::class);
        $this->nucleiEnvMock = $this->createMock(Env::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->testResultRepositoryMock = $this->createMock(TestResultRepository::class);
        $this->testResultFactoryMock = $this->createMock(TestResultFactory::class);
        $this->testResultImageRepositoryMock = $this->createMock(TestResultImageRepository::class);

        $this->requestMock = $this->createMock(Request::class);
        $this->requestMock->files = $this->createMock(FileBag::class);
        $this->requestMock->request = $this->createMock(ParameterBag::class);
    }

    protected function mock(): NucleiResults
    {
        return new NucleiResults(
            $this->sspConfigMock,
            $this->moduleConfigurationMock,
            $this->templateFactoryMock,
            $this->authorizationMock,
            $this->metaDataStorageHandlerMock,
            $this->nucleiEnvMock,
            $this->helpersMock,
            $this->loggerMock,
            $this->testResultRepositoryMock,
            $this->testResultFactoryMock,
            $this->testResultImageRepositoryMock,
        );
    }
    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(NucleiResults::class, $this->mock());
    }

    public function testCanShowIndexForAllSps(): void
    {
        $this->metaDataStorageHandlerMock->method('getList')->willReturn([]);
        $this->testResultRepositoryMock->expects($this->once())->method('getLatest')->willReturn([]);

        $this->assertInstanceOf(Template::class, $this->mock()->index($this->requestMock));
    }

    public function testCanShowIndexForSingleSps(): void
    {
        $this->requestMock->method('get')->willReturn('sp-entity-id');
        $this->metaDataStorageHandlerMock->method('getList')->willReturn([]);
        $this->testResultRepositoryMock->expects($this->once())->method('getLatest')->willReturn([]);

        $this->assertInstanceOf(Template::class, $this->mock()->index($this->requestMock));
    }
}
