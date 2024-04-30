<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Database\Repositories;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultRepository;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestResultRepository::class)]
class TestResultRepositoryTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
