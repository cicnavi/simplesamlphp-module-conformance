<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Factories;

use JsonException;
use SimpleSAML\Module\conformance\Database\Repositories\TestResultRepository;
use SimpleSAML\Module\conformance\Entities\Nuclei\TestResult;
use SimpleSAML\Module\conformance\Errors\ConformanceException;

class TestResultFactory
{
    /**
     * @throws ConformanceException|JsonException
     */
    public function fromRow(array $row): TestResult
    {
        $this->validateRow($row);

        return new TestResult(
            (int)$row[TestResultRepository::COLUMN_ID],
            (string)$row[TestResultRepository::COLUMN_ENTITY_ID],
            (int)$row[TestResultRepository::COLUMN_HAPPENED_AT],
            isset($row[TestResultRepository::COLUMN_NUCLEI_JSON_RESULT]) &&
                (!empty($row[TestResultRepository::COLUMN_NUCLEI_JSON_RESULT])) ?
                    (string)$row[TestResultRepository::COLUMN_NUCLEI_JSON_RESULT] : null,
            isset($row[TestResultRepository::COLUMN_NUCLEI_FINDINGS]) &&
                (!empty($row[TestResultRepository::COLUMN_NUCLEI_FINDINGS])) ?
                    (string)$row[TestResultRepository::COLUMN_NUCLEI_FINDINGS] : null,
        );
    }

    /**
     * @throws ConformanceException
     */
    protected function validateRow(array $row): void
    {
        $mandatoryKeys = [
            TestResultRepository::COLUMN_ID,
            TestResultRepository::COLUMN_ENTITY_ID,
            TestResultRepository::COLUMN_HAPPENED_AT,
        ];

        $missingKeys = array_diff($mandatoryKeys, array_keys($row));

        if (!empty($missingKeys)) {
            $message = sprintf(
                'Invalid row array for TestResult initialization; missing keys: %s.',
                implode(', ', $missingKeys)
            );
            throw new ConformanceException($message);
        }
    }
}
