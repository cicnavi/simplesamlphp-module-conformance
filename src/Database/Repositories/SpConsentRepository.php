<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database\Repositories;

use DateTime;
use PDO;
use SimpleSAML\Module\conformance\Database\AbstractDbEntity;

class SpConsentRepository extends AbstractDbEntity
{
    final public const COLUMN_ENTITY_ID = 'entity_id';
    final public const COLUMN_CREATED_AT = 'created_at';

    public static function getTableName(): string
    {
        return 'sp_consents';
    }

    public function getAll(): array
    {
        $stmt = $this->database->read(
            "SELECT * FROM {$this->getPrefixedTableName()} " .
            "ORDER BY " . self::COLUMN_ENTITY_ID
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    public function get(string $spEntityId): ?array
    {
        $stmt = $this->database->read(
            "SELECT * FROM {$this->getPrefixedTableName()} WHERE " . self::COLUMN_ENTITY_ID . " = :" .
            self::COLUMN_ENTITY_ID . " LIMIT 1",
            [
                self::COLUMN_ENTITY_ID => $spEntityId,
            ]
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $row = current($rows);

        if (empty($row)) {
            return null;
        }

        return $row;
    }

    public function add(string $spEntityId): void
    {
        $this->database->write(
            <<<EOT
                INSERT IGNORE INTO {$this->getPrefixedTableName()} (
                    {$this->noop(self::COLUMN_ENTITY_ID)},
                    {$this->noop(self::COLUMN_CREATED_AT)}
                )
                VALUES (
                    :{$this->noop(self::COLUMN_ENTITY_ID)},
                    :{$this->noop(self::COLUMN_CREATED_AT)}
                )
            EOT,
            [
                self::COLUMN_ENTITY_ID => $spEntityId,
                self::COLUMN_CREATED_AT => (new DateTime())->getTimestamp(),
            ]
        );
    }
}
