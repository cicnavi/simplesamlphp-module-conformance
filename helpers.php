<?php

declare(strict_types=1);

if (!function_exists('dd')) {
    function dd(mixed ...$values): void
    {
        var_dump(...$values);
        die();
    }
}

if (!function_exists('noop')) {
    /**
     * Noop, marks the string for translation but returns it unchanged.
     */
    function noop(string $original): string
    {
        return $original;
    }
}