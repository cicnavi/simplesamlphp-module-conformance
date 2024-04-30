<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\SspBridge;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\SspBridge\Auth;
use PHPUnit\Framework\TestCase;

#[CoversClass(Auth::class)]
class AuthTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
