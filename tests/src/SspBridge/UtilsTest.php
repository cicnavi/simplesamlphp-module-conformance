<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\SspBridge;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\SspBridge\Utils;
use PHPUnit\Framework\TestCase;

#[CoversClass(Utils::class)]
class UtilsTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
