<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database;

interface MigrationInterface
{
    public function run(): void;
}
