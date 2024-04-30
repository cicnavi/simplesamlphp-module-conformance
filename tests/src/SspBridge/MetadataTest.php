<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\SspBridge;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\SspBridge\Metadata;
use PHPUnit\Framework\TestCase;

#[CoversClass(Metadata::class)]
class MetadataTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
