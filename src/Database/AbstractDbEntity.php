<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database;

use SimpleSAML\Configuration;
use SimpleSAML\Database;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
abstract class AbstractDbEntity
{
    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected Database $database,
        protected Helpers $helpers,
    ) {
    }

    abstract public static function getTableName(): string;

    public function getPrefixedTableName(?string $forTable = null): string
    {
        return $this->helpers->database()->getTableName(
            $this->moduleConfiguration->getDatabaseTableNamesPrefix(),
            $forTable ?? $this->getTableName(),
        );
    }

    protected function noop(string $val): string
    {
        return $val;
    }
}
