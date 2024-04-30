<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Database\Repositories;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Module\conformance\Database\Repositories\SpConsentRequestRepository;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpConsentRequestRepository::class)]
class SpConsentRequestRepositoryTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $this->markTestIncomplete();
    }
}
