<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Database;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Database\Migrator;
use PHPUnit\Framework\TestCase;

#[CoversClass(Migrator::class)]
class MigratorTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
