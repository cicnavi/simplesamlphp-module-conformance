<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Helpers\Database;
use PHPUnit\Framework\TestCase;

#[CoversClass(Database::class)]
class DatabaseTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
