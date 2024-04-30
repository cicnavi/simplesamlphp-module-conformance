<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Nuclei;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Nuclei\Env;
use PHPUnit\Framework\TestCase;

#[CoversClass(Env::class)]
class EnvTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
