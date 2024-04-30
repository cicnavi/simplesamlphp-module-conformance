<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

class Localization
{
    public function noop(string $original): string
    {
        return $original;
    }
}
