<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\SspBridge\Metadata;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\SspBridge\Metadata\SAMLParser;
use PHPUnit\Framework\TestCase;

#[CoversClass(SAMLParser::class)]
class SAMLParserTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
