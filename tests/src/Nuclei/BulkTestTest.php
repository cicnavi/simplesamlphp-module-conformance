<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Nuclei;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Nuclei\BulkTest;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkTest::class)]
class BulkTestTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
