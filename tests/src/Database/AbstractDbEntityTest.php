<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Database;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Database\AbstractDbEntity;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractDbEntity::class)]
class AbstractDbEntityTest extends TestCase
{
    public function testCanExtend(): void
    {
        $this->markTestIncomplete();
    }
}
