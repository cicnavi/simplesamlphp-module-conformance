<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\Module\conformance\Cache;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\conformance\Errors\CacheException;

/**
 * @covers \SimpleSAML\Module\conformance\Cache
 */
class CacheTest extends TestCase
{
    protected MockObject $cacheInterfaceMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->cacheInterfaceMock = $this->createMock(CacheInterface::class);
    }

    /**
     * @throws CacheException
     */
    protected function mocked(): Cache
    {
        return new Cache($this->cacheInterfaceMock);
    }
    public function testCanInitialize(): void
    {
        $this->assertInstanceOf(Cache::class, $this->mocked());
    }

    public function testCanGetTestId(): void
    {
        $spEntityId = 'test-sp-id';
        $this->cacheInterfaceMock->expects($this->once())->method('get')->with($this->isType('string'))
            ->willReturn('testId');
        $this->cacheInterfaceMock->expects($this->once())->method('delete')
            ->with($this->isType('string'));

        $this->assertIsString($this->mocked()->getTestId($spEntityId));
    }

    public function testCanGetNullForTestId(): void
    {
        $this->assertNull($this->mocked()->getTestId('test-sp-id'));
    }

    public function testGettingTestIdThrows(): void
    {
        $this->expectException(CacheException::class);

        $this->cacheInterfaceMock->method('get')->willThrowException(new \Exception());

        $this->mocked()->getTestId('test-sp-id');
    }

    public function testDeletingTestIdThrows(): void
    {
        $this->expectException(CacheException::class);

        $this->cacheInterfaceMock->method('get')->willReturn('test-id');
        $this->cacheInterfaceMock->method('delete')->willThrowException(new \Exception());

        $this->mocked()->getTestId('test-sp-id');
    }

    public function testSetTestId(): void
    {
        $this->cacheInterfaceMock->expects($this->once())->method('set')
            ->with($this->isType('string'), $this->isType('string'), $this->isType('integer'));

        $this->mocked()->setTestId('test-id', 'sp-test-id');
    }

    public function testSetTestIdThrows(): void
    {
        $this->expectException(CacheException::class);
        $this->cacheInterfaceMock->method('set')->willThrowException(new \Exception());

        $this->mocked()->setTestId('test-id', 'sp-test-id');
    }
}
