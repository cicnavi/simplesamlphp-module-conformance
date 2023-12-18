<?php

namespace SimpleSAML\Test\Module\conformance;

use SimpleSAML\Module\conformance\SspBridge;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\conformance\SspBridge
 * @uses \SimpleSAML\Module\conformance\SspBridge\Utils
 * @uses \SimpleSAML\Module\conformance\SspBridge\Module
 * @uses \SimpleSAML\Module\conformance\SspBridge\Auth
 */
class SspBridgeTest extends TestCase
{
    protected function mocked(): SspBridge
    {
        return new SspBridge();
    }

    public function testCanInstantiate(): void
    {
        $sspBridge = $this->mocked();
        $this->assertInstanceOf(SspBridge\Utils::class, $sspBridge->utils());
        $this->assertInstanceOf(SspBridge\Module::class, $sspBridge->module());
        $this->assertInstanceOf(SspBridge\Auth::class, $sspBridge->auth());
    }
}
