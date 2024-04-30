<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Nuclei\BulkTest;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Nuclei\BulkTest\State;
use PHPUnit\Framework\TestCase;

#[CoversClass(State::class)]
class StateTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
