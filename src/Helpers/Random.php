<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

use Exception;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use Throwable;

class Random
{
    /**
     * @throws Exception
     */
    public function int(int $minimum = PHP_INT_MIN, int $maximum = PHP_INT_MAX): int
    {
        try {
            return random_int($minimum, $maximum);
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            return random_int($minimum, $maximum);
            // @codeCoverageIgnoreEnd
        }
    }
    /**
     * @throws ConformanceException
     */
    public function string(int $length = 32): string
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
