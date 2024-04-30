<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Controllers\TestSetup;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestSetup::class)]
class TestSetupTest extends TestCase
{
    public function testCanInitialize(): void
    {
        $this->markTestIncomplete();
    }
}
