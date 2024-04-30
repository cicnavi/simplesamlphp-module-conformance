<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Controllers\SpConsent;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpConsent::class)]
class SpConsentTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
