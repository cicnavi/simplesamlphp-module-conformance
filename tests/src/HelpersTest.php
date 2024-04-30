<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Helpers;
use PHPUnit\Framework\TestCase;

#[CoversClass(Helpers::class)]
class HelpersTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
