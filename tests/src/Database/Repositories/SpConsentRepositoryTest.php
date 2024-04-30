<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Database\Repositories;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Database\Repositories\SpConsentRepository;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpConsentRepository::class)]
class SpConsentRepositoryTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
