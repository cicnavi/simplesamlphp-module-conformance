<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Helpers\Random;
use PHPUnit\Framework\TestCase;

#[CoversClass(Random::class)]
class RandomTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
