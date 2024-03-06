<?php

declare(strict_types=1);

use SimpleSAML\Module\conformance\Database\AbstractMigration;

class _1_CreateSpConsentsTable extends AbstractMigration
{
    public function run(): void
    {
        $this->database->write(<<< EOT
        CREATE TABLE {$this->getPrefixedTableName()} (
            entity_id VARCHAR(255) PRIMARY KEY NOT NULL,
            status char(16) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
EOT
        );
    }

    public function getTableName(): string
    {
        return 'sp_consents';
    }
}