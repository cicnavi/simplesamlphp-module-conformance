<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database;

use SimpleSAML\Configuration;
use SimpleSAML\Database;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;

abstract class AbstractMigration
{
    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected Database $database,
        protected Helpers $helpers,
    ) {
    }

    /**
     * @throws ConformanceException
     */
    abstract public function run(): void;

    abstract public function getTableName(): string;

    public function getPrefixedTableName(): string
    {
        return $this->helpers->database()->getTableName(
            $this->moduleConfiguration->getDatabaseTableNamesPrefix(),
            $this->getTableName(),
        );
    }
}
