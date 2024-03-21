<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database\Repositories;

use PDO;
use SimpleSAML\Module\conformance\Database\AbstractDbEntity;

class SpConsentRequestRepository extends AbstractDbEntity
{
    final public const COLUMN_ENTITY_ID = 'entity_id';
    final public const COLUMN_CHALLENGE = 'challenge';
    final public const COLUMN_CREATED_AT = 'created_at';
    final public const COLUMN_CONTACT_EMAIL = 'contact_email';

    public static function getTableName(): string
    {
        return 'sp_consent_requests';
    }

    public function get(string $spEntityId, string $contactEmail = null): ?array
    {
        // Delete expired challenges for this SP
        $expirationTimestamp = (new \DateTime())->getTimestamp() -
            $this->moduleConfiguration->getSpConsentChallengeTimeToLive();

        $this->database->write(
            "DELETE FROM {$this->getPrefixedTableName()} " .
            "WHERE " . self::COLUMN_ENTITY_ID . " = :" . self::COLUMN_ENTITY_ID . " AND " .
            self::COLUMN_CREATED_AT . " < :" . self::COLUMN_CREATED_AT,
            [
                self::COLUMN_ENTITY_ID => $spEntityId,
                self::COLUMN_CREATED_AT => $expirationTimestamp,
            ]
        );

        // Build read statement
        $sql = "SELECT * FROM {$this->getPrefixedTableName()} WHERE " .
            self::COLUMN_ENTITY_ID . " = :" . self::COLUMN_ENTITY_ID;
        $params = [self::COLUMN_ENTITY_ID => $spEntityId,];

        if ($contactEmail) {
            $sql .= " AND " . self::COLUMN_CONTACT_EMAIL . " = :" . self::COLUMN_CONTACT_EMAIL;
            $params[self::COLUMN_CONTACT_EMAIL]  = $contactEmail;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->database->read($sql, $params);

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

    public function generate(string $spEntityId, string $contactEmail): string
    {
        $challenge = $this->helpers->str()->random();

        $this->database->write(
            <<<EOT
                INSERT INTO {$this->getPrefixedTableName()} (
                    {$this->noop(self::COLUMN_ENTITY_ID)},
                    {$this->noop(self::COLUMN_CHALLENGE)},
                    {$this->noop(self::COLUMN_CONTACT_EMAIL)},
                    {$this->noop(self::COLUMN_CREATED_AT)}
                )
                VALUES (
                    :{$this->noop(self::COLUMN_ENTITY_ID)},
                    :{$this->noop(self::COLUMN_CHALLENGE)},
                    :{$this->noop(self::COLUMN_CONTACT_EMAIL)},
                    :{$this->noop(self::COLUMN_CREATED_AT)}
                )
                ON DUPLICATE KEY UPDATE
                    {$this->noop(self::COLUMN_CHALLENGE)} = VALUES({$this->noop(self::COLUMN_CHALLENGE)}),
                    {$this->noop(self::COLUMN_CONTACT_EMAIL)} = VALUES({$this->noop(self::COLUMN_CONTACT_EMAIL)}),
                    {$this->noop(self::COLUMN_CREATED_AT)} = VALUES({$this->noop(self::COLUMN_CREATED_AT)})
            EOT,
            [
                self::COLUMN_ENTITY_ID => $spEntityId,
                self::COLUMN_CHALLENGE => $challenge,
                self::COLUMN_CONTACT_EMAIL => $contactEmail,
                self::COLUMN_CREATED_AT => (new \DateTime())->getTimestamp(),
            ]
        );

        return $challenge;
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
