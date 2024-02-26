<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Menu;

use PHPUnit\Framework\Attributes\UsesClass;
use SimpleSAML\Module\conformance\Menu\Item;
use PHPUnit\Framework\TestCase;

#[UsesClass(Item::class)]
class ItemTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
