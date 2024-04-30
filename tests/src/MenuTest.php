<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Menu;
use PHPUnit\Framework\TestCase;

#[CoversClass(Menu::class)]
class MenuTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
