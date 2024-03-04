<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Filesystem
{
    final public const KEY_SORT_ASC = 'asc';
    final public const KEY_SORT_DESC = 'desc';
    final public const KEY_SORT_NO_SORT = 'no-sort';


    public function cleanFilename(string $filename): string
    {
        // Remove any characters that are not allowed in filenames
        $filename = preg_replace('/[^\w\s\d\-_~,;\[\]\(\).]/', '', $filename);

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Trim the filename to a reasonable length
        return substr($filename, 0, 255);
    }

    public function getPathFromElements(string ...$elements): string
    {
        array_walk($elements, function (string &$element) {
            // Remove trailing slashes
            $element = rtrim($element, DIRECTORY_SEPARATOR);
        });

        /** @var string[] $elements */
        return implode(DIRECTORY_SEPARATOR, $elements);
    }

    public function listFilesInDirectory(
        string $directory,
        string $sortOrder = self::KEY_SORT_ASC,
        bool $onlyReturnFileName = true,
    ): array
    {
        $files = [];

        // Create a RecursiveDirectoryIterator instance
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        // Iterate through each file in the directory
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {

            // Check if it's a regular file (not a directory)
            if (!$file->isFile()) {
                continue;
            }

            $fileName = $file->getPathname();

            if ($onlyReturnFileName) {
                $fileName = str_replace($directory, '', $fileName);
                $fileName = ltrim($fileName, DIRECTORY_SEPARATOR);
            }

            $files[] = $fileName;
        }

        if ($sortOrder === self::KEY_SORT_ASC) {
            asort($files);
        } elseif ($sortOrder === self::KEY_SORT_DESC) {
            arsort($files);
        }

        return $files;
    }
}
