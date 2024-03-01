<?php

namespace SimpleSAML\Test\Module\conformance;

use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use SimpleSAML\Module\conformance\GenericStatus;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\conformance\GenericStatus
 */
class GenericStatusTest extends TestCase
{
    public function testCanInstantiateWithoutParameters(): void
    {
        $status = new GenericStatus();
        $this->assertInstanceOf(GenericStatus::class, $status);
        $this->assertNull($status->getStatus());
        $this->assertNull($status->getMessage());
    }

    public function testCanSetStatusOnInstantiation()
    {
        $status = new GenericStatus(GenericStatus::STATUS_OK, 'test');
        $this->assertInstanceOf(GenericStatus::class, $status);
        $this->assertSame(GenericStatus::STATUS_OK, $status->getStatus());
        $this->assertSame('test', $status->getMessage());
    }

    public function testCanSetStatus(): void
    {
        $status = new GenericStatus();
        $this->assertNull($status->getStatus());
        $status->setStatus(GenericStatus::STATUS_OK);
        $this->assertSame(GenericStatus::STATUS_OK, $status->getStatus());
        $this->assertTrue($status->isOk());
        $status->setStatus(null);
        $this->assertNull($status->getStatus());
        $this->assertNull($status->isOk());
        $status->setStatusOk();
        $this->assertSame(GenericStatus::STATUS_OK, $status->getStatus());
        $status->setStatusError();
        $this->assertTrue($status->isError());
        $this->assertSame(GenericStatus::STATUS_ERROR, $status->getStatus());

        $this->assertNull($status->getMessage());
        $status->setMessage('test');
        $this->assertSame('test', $status->getMessage());
        $status->setMessage(null);
        $this->assertNull($status->getMessage());
    }

    public function testCanTransformToArray(): void
    {
        $status = new GenericStatus(GenericStatus::STATUS_OK, 'test');
        $expected = [
            GenericStatus::KEY_STATUS => GenericStatus::STATUS_OK,
            GenericStatus::KEY_MESSAGE => 'test',
        ];
        $this->assertSame($expected, $status->toArray());
    }
}
