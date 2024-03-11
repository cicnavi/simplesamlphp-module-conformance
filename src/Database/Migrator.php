<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Database;

use PDO;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use SimpleSAML\Configuration;
use SimpleSAML\Database;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;

class Migrator
{
    final public const TABLE_NAME_MIGRATIONS = 'migrations';

    final public const DIRECTORY_NAME_MIGRATIONS = 'migrations';

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected Database $database,
        protected Helpers $helpers,
        protected LoggerInterface $logger,
    ) {
        $this->ensureMigrationsTable();
    }

    public function getAllMigrations(): array
    {
        return $this->helpers->filesystem()->listFilesInDirectory($this->getMigrationsDirectory());
    }

    public function getImplementedMigrations(): array
    {
        return $this->database
            ->read("SELECT migration FROM {$this->getTableName()}")
            ->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getNonImplementedMigrations(): array
    {
        return array_diff($this->getAllMigrations(), $this->getImplementedMigrations());
    }

    /**
     * @throws ConformanceException
     */
    public function runNonImplementedMigrations(): void
    {
        /** @var string $migration */
        foreach ($this->getNonImplementedMigrations() as $migration) {
            $migrationFilePath = $this->helpers->filesystem()->getPathFromElements(
                $this->getMigrationsDirectory(),
                $migration,
            );

            if (!file_exists($migrationFilePath)) {
                throw new ConformanceException('Invalid migration file path: ' . $migrationFilePath);
            }

            require $migrationFilePath;

            // We expect that the class name is the same as the file name.
            $migrationClassName = str_replace('.php', '', $migration);

            if (! is_subclass_of($migrationClassName, AbstractDbEntity::class, true)) {
                $this->logger->warning(
                    "Migration $migration is not instance of " . AbstractDbEntity::class . ", skipping."
                );
                continue;
            }

            try {
                $migrationInstance = (new ReflectionClass($migrationClassName))
                    ->newInstance(
                        $this->sspConfig,
                        $this->moduleConfiguration,
                        $this->database,
                        $this->helpers,
                    );
            } catch (\ReflectionException $exception) {
                throw new ConformanceException(
                    'Could not create migration class instance, error was: ' .
                    $exception->getMessage()
                );
            }

            if (! is_a($migrationInstance, MigrationInterface::class)) {
                $this->logger->warning(
                    "Migration $migration does not implement " . MigrationInterface::class . ", skipping."
                );
                continue;
            }

            $migrationInstance->run();
            $this->markImplementedMigration($migration);
        }
    }

    public function getMigrationsDirectory(): string
    {
        return $this->helpers->filesystem()->getPathFromElements(
            $this->moduleConfiguration->getModuleRootDirectory(),
            self::DIRECTORY_NAME_MIGRATIONS,
        );
    }

    protected function ensureMigrationsTable(): void
    {
        $this->database->write(
            "CREATE TABLE IF NOT EXISTS {$this->getTableName()} (migration VARCHAR(191) PRIMARY KEY NOT NULL)"
        );
    }

    protected function getTableName(): string
    {
        return $this->moduleConfiguration->getDatabaseTableNamesPrefix() . self::TABLE_NAME_MIGRATIONS;
    }

    protected function markImplementedMigration(string $migration): void
    {
        $this->database->write("INSERT IGNORE INTO {$this->getTableName()} (migration) VALUES ('$migration')");
    }
}
