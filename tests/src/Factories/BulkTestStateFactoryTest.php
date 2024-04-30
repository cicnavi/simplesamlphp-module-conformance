<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Factories\BulkTestStateFactory;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkTestStateFactory::class)]
class BulkTestStateFactoryTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
