<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Controllers\Overview;
use PHPUnit\Framework\TestCase;

#[CoversClass(Overview::class)]
class OverviewTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
