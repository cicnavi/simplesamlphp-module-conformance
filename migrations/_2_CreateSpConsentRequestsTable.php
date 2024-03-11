<?php

declare(strict_types=1);

use SimpleSAML\Module\conformance\Database\AbstractDbEntity;
use SimpleSAML\Module\conformance\Database\MigrationInterface;

class _2_CreateSpConsentRequestsTable extends AbstractDbEntity implements MigrationInterface
{
    public function run(): void
    {
        $this->database->write(<<< EOT
        CREATE TABLE {$this->getPrefixedTableName()} (
            entity_id VARCHAR(255) PRIMARY KEY NOT NULL,
            challenge char(64) NOT NULL,
            created_at BIGINT UNSIGNED NOT NULL
        )
EOT
        );
    }

    public static function getTableName(): string
    {
        return 'sp_consent_requests';
    }
}