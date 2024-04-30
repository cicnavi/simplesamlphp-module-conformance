<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Database\Repositories;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultImageRepository;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestResultImageRepository::class)]
class TestResultImageRepositoryTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
