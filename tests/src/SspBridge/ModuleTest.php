<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\SspBridge;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\SspBridge\Module;
use PHPUnit\Framework\TestCase;

#[CoversClass(Module::class)]
class ModuleTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
