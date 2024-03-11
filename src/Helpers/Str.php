<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

use SimpleSAML\Module\conformance\Errors\ConformanceException;
use Throwable;

class Str
{
    /**
     * @throws ConformanceException
     */
    public function random(int $length = 32): string
    {
        if ($length < 1) {
            throw new ConformanceException('Random string length can not be less than 1');
        }

        try {
            return bin2hex(random_bytes($length));
        } catch (Throwable $e) {
            throw new ConformanceException('Could not generate a random string');
        }
    }
}
