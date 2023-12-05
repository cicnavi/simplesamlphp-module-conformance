<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Auth\Process;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\conformance\Cache;
use SimpleSAML\Module\conformance\Helpers\StateHelper;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\SspBridge;

/**
 * @covers \SimpleSAML\Module\conformance\Auth\Process\Conformance
 * @uses \SimpleSAML\Auth\ProcessingFilter
 */
class ConformanceTest extends TestCase
{
    protected const CONFIG = [];
    protected MockObject $cacheMock;
    protected MockObject $responderResolverMock;
    protected MockObject $stateHelperMock;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(Cache::class);
        $this->responderResolverMock = $this->createMock(ResponderResolver::class);
        $this->stateHelperMock = $this->createMock(StateHelper::class);

        $this->sspBridgeUtilsMock = $this->createMock(SspBridge\Utils::class);
        $this->sspBridgeModuleMock = $this->createMock(SspBridge\Module::class);

        $this->sspBridgeAuthStateMock = $this->createMock(SspBridge\Auth\State::class);
        $this->sspBridgeAuthMock = $this->createMock(SspBridge\Auth::class);
        $this->sspBridgeAuthMock->method('state')->willReturn($this->sspBridgeAuthStateMock);

        $this->sspBridgeMock = $this->createMock(SspBridge::class);
        $this->sspBridgeMock->method('utils')->willReturn($this->sspBridgeUtilsMock);
        $this->sspBridgeMock->method('module')->willReturn($this->sspBridgeModuleMock);
        $this->sspBridgeMock->method('auth')->willReturn($this->sspBridgeAuthMock);
    }

    protected function mocked(array $config = null): Conformance
    {
        return new Conformance(
            $config ?? self::CONFIG,
            null,
            $this->cacheMock,
            $this->responderResolverMock,
            $this->stateHelperMock,
        );
    }

    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(Conformance::class, $this->mocked());
    }
}
