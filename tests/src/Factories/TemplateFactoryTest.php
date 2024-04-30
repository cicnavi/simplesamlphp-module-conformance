<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use PHPUnit\Framework\TestCase;

#[CoversClass(TemplateFactory::class)]
class TemplateFactoryTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
