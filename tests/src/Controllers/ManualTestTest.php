<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Controllers\ManualTest;
use PHPUnit\Framework\TestCase;

#[CoversClass(ManualTest::class)]
class ManualTestTest extends TestCase
{
    public function testCanInitialize(): void
    {
        $this->markTestIncomplete();
    }
}
