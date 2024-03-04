<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

class Database
{
    public function getTableName(string ...$elements): string
    {
        // Get single string from all elements.
        $tableName = implode('', $elements);

        // Remove any characters that are not alphanumeric or underscores.
        $tableName = preg_replace('/\W/', '_', $tableName);

        // Ensure that the table name starts with a letter
        if (!ctype_alpha($tableName[0])) {
            $tableName = 't_' . $tableName;
        }

        // Limit the length of the table name to 64 characters (MariaDB's maximum)
        return substr($tableName, 0, 64);
    }
}