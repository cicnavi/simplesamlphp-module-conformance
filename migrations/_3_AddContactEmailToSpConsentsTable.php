<?php

declare(strict_types=1);

use SimpleSAML\Module\conformance\Database\AbstractDbEntity;
use SimpleSAML\Module\conformance\Database\MigrationInterface;

class _3_AddContactEmailToSpConsentsTable extends AbstractDbEntity implements MigrationInterface
{
    public function run(): void
    {
        $this->database->write(<<< EOT
        ALTER TABLE {$this->getPrefixedTableName()}
            ADD contact_email VARCHAR(255) NOT NULL AFTER entity_id
        
EOT
        );
    }

    public static function getTableName(): string
    {
        return 'sp_consents';
    }
}
