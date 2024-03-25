<?php

declare(strict_types=1);

use SimpleSAML\Module\conformance\Database\AbstractDbEntity;
use SimpleSAML\Module\conformance\Database\MigrationInterface;

class _3_CreateTestResultsTable extends AbstractDbEntity implements MigrationInterface
{
    public function run(): void
    {
        $this->database->write(<<< EOT
        CREATE TABLE {$this->getPrefixedTableName()} (
            id BIGINT UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
            entity_id VARCHAR(255) NOT NULL,
            happened_at BIGINT UNSIGNED NOT NULL,
            nuclei_json_result JSON NULL,
            nuclei_findings TEXT NULL,
            UNIQUE (entity_id, happened_at)
        )
EOT
        );
    }

    public static function getTableName(): string
    {
        return 'test_results';
    }
}
