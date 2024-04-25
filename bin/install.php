#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Script which can be run to do the module installation which includes running database migrations.
 */

use SAML2\Compat\Ssp\Logger;
use SimpleSAML\Configuration;
use SimpleSAML\Database;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Module\conformance\Database\Migrator;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\ModuleConfiguration;

// This is the base directory of the SimpleSAMLphp installation
$baseDir = dirname(__FILE__, 4);

// Add library autoloader and configuration
require_once $baseDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . '_autoload.php';

echo 'Starting with module installation.' . PHP_EOL;

try {
    $sspConfiguration = Configuration::getInstance();
    $moduleConfiguration = new ModuleConfiguration();
    $database = Database::getInstance();
    $helpers = new Helpers();
    $logger = new Logger();

    $migrator = new Migrator(
        $sspConfiguration,
        $moduleConfiguration,
        $database,
        $helpers,
        $logger,
    );

    echo 'Ensuring that Conformance module DB migrations are run.' . PHP_EOL;
    $migrator->runNonImplementedMigrations();
    echo 'Done.' . PHP_EOL;

    echo 'Ensuring that MetaDataStorageHandlerPdo DB migrations are run.' . PHP_EOL;
    (new MetaDataStorageHandlerPdo(
        [], // This argument is not used by MetaDataStorageHandlerPdo.
    ))->initDatabase();
    echo 'Done.' . PHP_EOL;

    echo 'Done with module installation.' . PHP_EOL;
    return 0;
} catch (Throwable $exception) {
    echo 'There was an error with the installation script: ' . $exception->getMessage();
    return 1;
}
