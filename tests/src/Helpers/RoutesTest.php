<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Helpers\Routes;
use PHPUnit\Framework\TestCase;

#[CoversClass(Routes::class)]
class RoutesTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
