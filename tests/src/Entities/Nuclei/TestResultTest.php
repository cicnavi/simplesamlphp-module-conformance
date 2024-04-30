<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Entities\Nuclei;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Entities\Nuclei\TestResult;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestResult::class)]
class TestResultTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
