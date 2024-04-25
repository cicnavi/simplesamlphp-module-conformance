<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database\Repositories;

use PDO;
use SimpleSAML\Module\conformance\Database\AbstractDbEntity;

class TestResultImageRepository extends AbstractDbEntity
{
    final public const COLUMN_ID = 'id';
    final public const COLUMN_TEST_RESULT_ID = 'test_result_id';
    final public const COLUMN_DATA = 'data';
    final public const COLUMN_NAME = 'name';

    public static function getTableName(): string
    {
        return 'test_result_images';
    }

    public function add(int $testResultId, string $data, string $name): void
    {
        $this->database->write(
            <<<EOT
                INSERT INTO {$this->getPrefixedTableName()} (
                    {$this->noop(self::COLUMN_TEST_RESULT_ID)},
                    {$this->noop(self::COLUMN_DATA)},
                    {$this->noop(self::COLUMN_NAME)}
                )
                VALUES (
                    :{$this->noop(self::COLUMN_TEST_RESULT_ID)},
                    :{$this->noop(self::COLUMN_DATA)},
                    :{$this->noop(self::COLUMN_NAME)}
                )
            EOT,
            [
                self::COLUMN_TEST_RESULT_ID => $testResultId,
                self::COLUMN_DATA => $data,
                self::COLUMN_NAME => substr($name, 0, 255),
            ]
        );
    }

    /**
     * @param string[] $columns
     */
    public function getForTestResult(
        int $testResultId,
        array $columns = [self::COLUMN_ID, self::COLUMN_TEST_RESULT_ID,  self::COLUMN_DATA, self::COLUMN_NAME],
        int $limit = 10,
    ): array {
        $columnList = implode(', ', $columns);

        $sql = "SELECT $columnList FROM {$this->getPrefixedTableName()} " .
            "WHERE {$this->noop(self::COLUMN_TEST_RESULT_ID)} = :{$this->noop(self::COLUMN_TEST_RESULT_ID)} " .
            "LIMIT $limit";

        $params = [
            self::COLUMN_TEST_RESULT_ID => $testResultId,
        ];

        $stmt = $this->database->read($sql, $params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function getSpecificForTestResult(int $testResultId, int $imageId): ?array
    {
        $sql = "SELECT * FROM {$this->getPrefixedTableName()} " .
            "WHERE {$this->noop(self::COLUMN_ID)} = :{$this->noop(self::COLUMN_ID)} AND " .
            "{$this->noop(self::COLUMN_TEST_RESULT_ID)} = :{$this->noop(self::COLUMN_TEST_RESULT_ID)}";

        $params = [
            self::COLUMN_ID => $imageId,
            self::COLUMN_TEST_RESULT_ID => $testResultId,
        ];

        $stmt = $this->database->read($sql, $params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }
}
