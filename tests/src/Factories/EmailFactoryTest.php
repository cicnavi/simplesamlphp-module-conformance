<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Factories\EmailFactory;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailFactory::class)]
class EmailFactoryTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
