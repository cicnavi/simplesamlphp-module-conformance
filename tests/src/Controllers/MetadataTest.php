<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Controllers\Metadata;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\conformance\Factories\GenericStatusFactory;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\SspBridge;
use SimpleSAML\Utils\XML;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(Metadata::class)]
class MetadataTest extends TestCase
{
    protected MockObject $sspConfigMock;
    protected MockObject $moduleConfigurationMock;
    protected MockObject $metaDataStorageHandlerPdoMock;
    protected MockObject $sspBridgeMock;
    protected MockObject $genericStatusFactoryMock;
    protected MockObject $templateFactoryMock;
    protected MockObject $authorizationMock;
    protected MockObject $helpersMock;
    protected MockObject $requestMock;
    protected MockObject $sspBridgeUtilsMock;
    protected MockObject $sspBridgeUtilsXmlMock;
    protected MockObject $uploadedFileMock;
    protected string $metadataFolder;
    protected MockObject $sspBridgeMetadataMock;
    protected MockObject $sspBridgeMetadataSamlParserMock;

    protected function setUp(): void
    {
        $this->sspConfigMock = $this->createMock(Configuration::class);
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
        $this->metaDataStorageHandlerPdoMock = $this->createMock(MetaDataStorageHandlerPdo::class);
        $this->sspBridgeMock = $this->createMock(SspBridge::class);
        $this->genericStatusFactoryMock = $this->createMock(GenericStatusFactory::class);
        $this->templateFactoryMock = $this->createMock(TemplateFactory::class);
        $this->authorizationMock = $this->createMock(Authorization::class);
        $this->helpersMock = $this->createMock(Helpers::class);

        $this->requestMock = $this->createMock(Request::class);
        $this->requestMock->files = $this->createMock(FileBag::class);
        $this->requestMock->request = $this->createMock(ParameterBag::class);

        $this->sspBridgeUtilsMock = $this->createMock(SspBridge\Utils::class);
        $this->sspBridgeUtilsXmlMock = $this->createMock(XML::class);
        $this->sspBridgeUtilsMock->method('xml')->willReturn($this->sspBridgeUtilsXmlMock);
        $this->sspBridgeMock->method('utils')->willReturn($this->sspBridgeUtilsMock);

        $this->sspBridgeMetadataMock = $this->createMock(SspBridge\Metadata::class);
        $this->sspBridgeMetadataSamlParserMock = $this->createMock(SspBridge\Metadata\SAMLParser::class);
        $this->sspBridgeMetadataMock->method('samlParser')->willReturn($this->sspBridgeMetadataSamlParserMock);
        $this->sspBridgeMock->method('metadata')->willReturn($this->sspBridgeMetadataMock);

        $this->uploadedFileMock = $this->createMock(UploadedFile::class);

        $this->metadataFolder = dirname(__FILE__, 3) . DIRECTORY_SEPARATOR .
        'metadata' . DIRECTORY_SEPARATOR;
    }

    protected function mock(): Metadata
    {
        return new Metadata(
            $this->sspConfigMock,
            $this->moduleConfigurationMock,
            $this->metaDataStorageHandlerPdoMock,
            $this->sspBridgeMock,
            $this->genericStatusFactoryMock,
            $this->templateFactoryMock,
            $this->authorizationMock,
            $this->helpersMock,
        );
    }
    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(Metadata::class, $this->mock());
    }

    public function testCanPrepareTemplateForAdding(): void
    {
        $this->assertInstanceOf(Template::class, $this->mock()->add($this->requestMock));
    }

    public function testPersistErrorsOutIfNoDataProvided(): void
    {
        $this->assertSame(400, $this->mock()->persist($this->requestMock)->getStatusCode());
    }

    public function testPersistErrorsOutOnXmlFileException(): void
    {
        $this->uploadedFileMock->method('getPathname')->willReturn($this->metadataFolder . 'default-sp.xml');

        $this->requestMock->files->method('get')->willReturn($this->uploadedFileMock);
        $this->sspBridgeUtilsXmlMock->expects($this->once())->method('checkSAMLMessage')->willThrowException(
            new Exception('test')
        );

        $this->assertSame(400, $this->mock()->persist($this->requestMock)->getStatusCode());
    }

    public function testPersistErrorsOutOnXmlException(): void
    {
        $this->requestMock->request->method('get')->willReturn('invalid');
        $this->sspBridgeUtilsXmlMock->expects($this->once())->method('checkSAMLMessage')->willThrowException(
            new Exception('test')
        );

        $this->assertSame(400, $this->mock()->persist($this->requestMock)->getStatusCode());
    }

    public function testPersistErrorsOutOnXmlParseException(): void
    {
        $this->requestMock->request->method('get')->willReturn('invalid');
        $this->sspBridgeMetadataSamlParserMock->expects($this->once())->method('parseDescriptorsString')
            ->willThrowException(
                new Exception('test')
            );

        $this->assertSame(400, $this->mock()->persist($this->requestMock)->getStatusCode());
    }
}
