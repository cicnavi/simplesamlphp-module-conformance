<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Responder;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Responder\TestResponder;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestResponder::class)]
class TestResponderTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
