<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Auth\Process;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\conformance\Cache;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Helpers\StateHelper;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use SimpleSAML\Module\conformance\SspBridge;
use SimpleSAML\Utils\HTTP;

/**
 * @covers \SimpleSAML\Module\conformance\Auth\Process\Conformance
 * @uses \SimpleSAML\Auth\ProcessingFilter
 */
class ConformanceTest extends TestCase
{
    protected const SP_ENTITY_ID = 'sample-sp';
    protected const CONFIG = [];
    protected const STATE = ['sample' => 'state'];
    protected MockObject $cacheMock;
    protected MockObject $responderResolverMock;
    protected MockObject $stateHelperMock;
    protected MockObject $sspBridgeUtilsMock;
    protected MockObject $sspBridgeModuleMock;
    protected MockObject $sspBridgeAuthStateMock;
    protected MockObject $sspBridgeAuthMock;
    protected MockObject $sspBridgeMock;
    protected MockObject $sspBridgeUtilsHttpMock;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(Cache::class);
        $this->responderResolverMock = $this->createMock(ResponderResolver::class);
        $this->stateHelperMock = $this->createMock(StateHelper::class);

        $this->sspBridgeUtilsHttpMock = $this->createMock(HTTP::class);
        $this->sspBridgeUtilsMock = $this->createMock(SspBridge\Utils::class);
        $this->sspBridgeUtilsMock->method('http')->willReturn($this->sspBridgeUtilsHttpMock);

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
            $this->sspBridgeMock,
        );
    }

    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(Conformance::class, $this->mocked());
    }

    public function testRedirectToTestSelectPageIfNotPreSet(): void
    {
        $stateId = 'state-id';
        $path = 'conformance/test/setup';
        $url = 'https//sample.idp/' . $path;

        $this->stateHelperMock->method('resolveSpEntityId')->willReturn(self::SP_ENTITY_ID);

        $this->sspBridgeAuthStateMock->expects($this->once())->method('saveState')
            ->with(self::STATE)->willReturn($stateId);
        $this->sspBridgeModuleMock->expects($this->once())->method('getModuleURL')
            ->with($path)->willReturn($url);

        $this->sspBridgeUtilsHttpMock->expects($this->once())->method('redirectTrustedURL')
            ->with($url, $this->isType('array'));

        $state = self::STATE;
        $this->mocked()->process($state);
    }

    public function testCanResolveResponder(): void
    {
        $testId = 'sample-test-id';
        $responder = ['sample' => 'responder'];

        $this->stateHelperMock->method('resolveSpEntityId')->willReturn(self::SP_ENTITY_ID);
        $this->cacheMock->expects($this->once())->method('getTestId')->with(self::SP_ENTITY_ID)
            ->willReturn($testId);


        $this->responderResolverMock->expects($this->once())->method('fromTestId')
            ->with($testId)->willReturn($responder);

        $state = self::STATE;
        $this->mocked()->process($state);

        $this->assertArrayHasKey(Conformance::KEY_RESPONDER, $state);
        $this->assertSame($state[Conformance::KEY_RESPONDER], $responder);
    }

    public function testThrowsIfNoResponderForTest(): void
    {
        $testId = 'invalid';

        $this->stateHelperMock->method('resolveSpEntityId')->willReturn(self::SP_ENTITY_ID);
        $this->cacheMock->expects($this->once())->method('getTestId')->with(self::SP_ENTITY_ID)
            ->willReturn($testId);

        $this->responderResolverMock->expects($this->once())->method('fromTestId')->willReturn(null);

        $this->expectException(ConformanceException::class);

        $state = self::STATE;
        $this->mocked()->process($state);
    }

    public function testThrowsForCacheProblems(): void
    {
        $this->cacheMock->expects($this->once())->method('getTestId')->willThrowException(new \Exception());

        $this->expectException(ConformanceException::class);
        $state = [];
        $this->mocked()->process($state);
    }
}
