<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Controllers\NucleiTest;
use PHPUnit\Framework\TestCase;

#[CoversClass(NucleiTest::class)]
class NucleiTestTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
