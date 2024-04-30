<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Authorization;
use PHPUnit\Framework\TestCase;

#[CoversClass(Authorization::class)]
class AuthorizationTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
