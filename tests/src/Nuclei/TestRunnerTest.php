<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Nuclei;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Nuclei\TestRunner;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestRunner::class)]
class TestRunnerTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
