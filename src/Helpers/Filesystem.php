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
}
