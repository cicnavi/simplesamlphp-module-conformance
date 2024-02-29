<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

class Filesystem
{
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
}
