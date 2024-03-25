<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database\Repositories;

use DateTimeImmutable;
use PDO;
use SimpleSAML\Module\conformance\Database\AbstractDbEntity;

class TestResultRepository extends AbstractDbEntity
{
    final public const COLUMN_ENTITY_ID = 'entity_id';
    final public const COLUMN_HAPPENED_AT = 'happened_at';
    final public const COLUMN_NUCLEI_JSON_RESULT = 'nuclei_json_result';
    final public const COLUMN_NUCLEI_FINDINGS = 'nuclei_findings';

    public static function getTableName(): string
    {
        return 'test_results';
    }

    public function addForSp(
        string $spEntityId,
        int $happenedAt,
        string $nucleiJsonResult = null,
        string $nucleiFindings = null,
    ): void {
        $nucleiJsonResult = is_null($nucleiJsonResult) ? [$nucleiJsonResult => PDO::PARAM_NULL] : $nucleiJsonResult;
        $nucleiFindings = is_null($nucleiFindings) ? [$nucleiFindings => PDO::PARAM_NULL] : $nucleiFindings;

        $this->database->write(
            <<<EOT
                INSERT IGNORE INTO {$this->getPrefixedTableName()} (
                    {$this->noop(self::COLUMN_ENTITY_ID)},
                    {$this->noop(self::COLUMN_HAPPENED_AT)},
                    {$this->noop(self::COLUMN_NUCLEI_JSON_RESULT)},
                    {$this->noop(self::COLUMN_NUCLEI_FINDINGS)}
                )
                VALUES (
                    :{$this->noop(self::COLUMN_ENTITY_ID)},
                    :{$this->noop(self::COLUMN_HAPPENED_AT)},
                    :{$this->noop(self::COLUMN_NUCLEI_JSON_RESULT)},
                    :{$this->noop(self::COLUMN_NUCLEI_FINDINGS)}
                )
            EOT,
            [
                self::COLUMN_ENTITY_ID => $spEntityId,
                self::COLUMN_HAPPENED_AT => $happenedAt,
                self::COLUMN_NUCLEI_JSON_RESULT => $nucleiJsonResult,
                self::COLUMN_NUCLEI_FINDINGS => $nucleiFindings,
            ]
        );
    }

    public function getForSp(string $spEntityId, int $limit = 10): array
    {

        // Build read statement
        $sql = "SELECT * FROM {$this->getPrefixedTableName()} WHERE " .
            self::COLUMN_ENTITY_ID . " = :" . self::COLUMN_ENTITY_ID .
            " ORDER BY " . self::COLUMN_HAPPENED_AT . " DESC";
        ;
        $params = [self::COLUMN_ENTITY_ID => $spEntityId,];

        $sql .= " LIMIT $limit";

        $stmt = $this->database->read($sql, $params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function getLastForAllSps(): array
    {
        $stmt = $this->database->read(
            "SELECT * FROM {$this->getPrefixedTableName()} " .
            "ORDER BY " . self::COLUMN_ENTITY_ID
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    public function delete(string $spEntityId): void
    {
        $this->database->write(
            "DELETE FROM {$this->getPrefixedTableName()} " .
            "WHERE " . self::COLUMN_ENTITY_ID . " = :" . self::COLUMN_ENTITY_ID,
            [
                self::COLUMN_ENTITY_ID => $spEntityId,
            ]
        );
    }
}
