<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Helpers\State;
use PHPUnit\Framework\TestCase;

#[CoversClass(State::class)]
class StateTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
