<?php

use JetBrains\PhpStorm\NoReturn;

if (!function_exists('dd')) {
    #[NoReturn]
    function dd(mixed ...$values): void
    {
        die(var_dump(...$values));
    }
}