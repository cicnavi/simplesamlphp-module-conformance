<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Controllers;

use PHPUnit\Framework\Attributes\UsesClass;
use SimpleSAML\Module\conformance\Controllers\NucleiResults;
use PHPUnit\Framework\TestCase;

#[UsesClass(NucleiResults::class)]
class NucleiResultsTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
