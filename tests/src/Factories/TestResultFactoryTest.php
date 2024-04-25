<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\conformance\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultRepository;
use SimpleSAML\Module\conformance\Entities\Nuclei\TestResult;
use SimpleSAML\Module\conformance\Factories\TestResultFactory;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestResultFactory::class)]
#[UsesClass(TestResult::class)]
class TestResultFactoryTest extends TestCase
{
    protected array $row = [
        TestResultRepository::COLUMN_ID => 1,
        TestResultRepository::COLUMN_ENTITY_ID => 'urn:sample',
        TestResultRepository::COLUMN_HAPPENED_AT => 1713170252,
        'random-key' => 'random-value',
    ];
    protected function setUp(): void
    {
    }

    public function testFromRow(): void
    {
        $this->assertInstanceOf(TestResult::class, (new TestResultFactory())->fromRow($this->row));
    }
}
