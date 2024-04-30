<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Responder;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Responder\ResponderResolver;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponderResolver::class)]
class ResponderResolverTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
