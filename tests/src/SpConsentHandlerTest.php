<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\SpConsentHandler;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpConsentHandler::class)]
class SpConsentHandlerTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
