<?php

namespace SimpleSAML\Test\Module\conformance;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\conformance\GenericStatus;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;

/**
 * @covers \SimpleSAML\Module\conformance\Factories\GenericStatusFactory
 * @uses \SimpleSAML\Module\conformance\GenericStatus
 */
class GenericStatusFactoryTest extends TestCase
{
    protected MockObject $requestMock;
    protected Stub $serverBagMock;
    protected Stub $inputBagMock;

    protected function setUp(): void
    {
        $this->serverBagMock = $this->createStub(ServerBag::class);
        $this->inputBagMock = $this->createStub(ParameterBag::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->requestMock->server = $this->serverBagMock;
        $this->requestMock->query = $this->inputBagMock;
    }

    protected function mocked(): \SimpleSAML\Module\conformance\Factories\GenericStatusFactory
    {
        return new \SimpleSAML\Module\conformance\Factories\GenericStatusFactory();
    }

    public function testCanCreateGenericStatus(): void
    {
        $this->assertInstanceOf(GenericStatus::class, $this->mocked()->fromRequest($this->requestMock));
    }

    public function testCanSetStatusUsingServerVariable(): void
    {
        $this->markTestIncomplete();
    }

    public function testCanSetStatusUsingQueryVariable(): void
    {
        $this->markTestIncomplete();
    }
}
