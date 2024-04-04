<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database\Repositories;

use DateTimeImmutable;
use PDO;
use SimpleSAML\Module\conformance\Database\AbstractDbEntity;

class TestResultRepository extends AbstractDbEntity
{
    final public const COLUMN_ID = 'id';
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

    public function get(string $spEntityId = null, int $limit = 100, int $offset = 0): array
    {
        // Build read statement
        $sql = "SELECT * FROM {$this->getPrefixedTableName()} ";
        $params = [];

        if (!is_null($spEntityId)) {
            $sql .= "WHERE {$this->noop(self::COLUMN_ENTITY_ID)} = :{$this->noop(self::COLUMN_ENTITY_ID)} ";
            $params[self::COLUMN_ENTITY_ID] = $spEntityId;
        }

        $sql .= "ORDER BY {$this->noop(self::COLUMN_ENTITY_ID)} ASC, " .
            "{$this->noop(self::COLUMN_HAPPENED_AT)} DESC " .
            "LIMIT $limit OFFSET $offset";

        $stmt = $this->database->read($sql, $params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function getLatest(string $spEntityId = null, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT ctr.* FROM {$this->getPrefixedTableName()} as ctr ";
        $params = [];

        $sql .= "INNER JOIN " .
            "(" .
                "SELECT " .
                    "{$this->noop(self::COLUMN_ENTITY_ID)}, " .
                    "MAX({$this->noop(self::COLUMN_HAPPENED_AT)}) AS {$this->noop(self::COLUMN_HAPPENED_AT)} " .
                "FROM {$this->getPrefixedTableName()} " .
                "GROUP BY {$this->noop(self::COLUMN_ENTITY_ID)} " .
            ") AS ctrl " .
            "ON ctr.{$this->noop(self::COLUMN_ENTITY_ID)} = ctrl.{$this->noop(self::COLUMN_ENTITY_ID)} AND " .
                "ctr.{$this->noop(self::COLUMN_HAPPENED_AT)} = ctrl.{$this->noop(self::COLUMN_HAPPENED_AT)} ";

        if (!is_null($spEntityId)) {
            $sql .= "WHERE ctr.{$this->noop(self::COLUMN_ENTITY_ID)} = :{$this->noop(self::COLUMN_ENTITY_ID)} ";
            $params[self::COLUMN_ENTITY_ID] = $spEntityId;
        }

        $sql .= "ORDER BY {$this->noop(self::COLUMN_ENTITY_ID)} ASC, " .
            "{$this->noop(self::COLUMN_HAPPENED_AT)} DESC " .
            "LIMIT $limit OFFSET $offset";

        $stmt = $this->database->read($sql, $params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function deleteObsolete(string $spEntityId, int $recordsToKeep = 10): void
    {
        $recordsToKeep = max(1, $recordsToKeep);

        $this->database->write(
            "DELETE FROM {$this->getPrefixedTableName()} " .
            "WHERE {$this->noop(self::COLUMN_ENTITY_ID)} = :{$this->noop(self::COLUMN_ENTITY_ID)} AND " .
            "{$this->noop(self::COLUMN_HAPPENED_AT)} < " .
            "(" .
            "SELECT MIN(sorted.{$this->noop(self::COLUMN_HAPPENED_AT)}) FROM " .
                "(" .
                    "SELECT {$this->noop(self::COLUMN_HAPPENED_AT)} " .
                    "FROM {$this->getPrefixedTableName()} " .
                    "WHERE {$this->noop(self::COLUMN_ENTITY_ID)} = :{$this->noop(self::COLUMN_ENTITY_ID)}2 " .
                    "ORDER BY {$this->noop(self::COLUMN_HAPPENED_AT)} DESC " .
                    "LIMIT $recordsToKeep " .
                ") as sorted " .
            ")",
            [
                self::COLUMN_ENTITY_ID => $spEntityId,
                self::COLUMN_ENTITY_ID . '2' => $spEntityId,
            ]
        );
    }
}
