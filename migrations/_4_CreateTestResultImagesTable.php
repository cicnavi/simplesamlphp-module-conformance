<?php

declare(strict_types=1);

use SimpleSAML\Module\conformance\Database\AbstractDbEntity;
use SimpleSAML\Module\conformance\Database\MigrationInterface;

class _4_CreateTestResultImagesTable extends AbstractDbEntity implements MigrationInterface
{
    public function run(): void
    {
        $this->database->write(<<< EOT
        CREATE TABLE {$this->getPrefixedTableName()} (
            id BIGINT UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
            test_result_id BIGINT UNSIGNED NOT NULL,
            data LONGBLOB NOT NULL,
            name VARCHAR(255) NOT NULL,
            FOREIGN KEY (test_result_id)
                REFERENCES {$this->getPrefixedTableName('test_results')} (id)
                ON DELETE CASCADE
        )
EOT
        );
    }

    public static function getTableName(): string
    {
        return 'test_result_images';
    }
}
