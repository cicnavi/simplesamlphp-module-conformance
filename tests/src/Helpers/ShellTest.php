<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Helpers\Shell;
use PHPUnit\Framework\TestCase;

#[CoversClass(Shell::class)]
class ShellTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
