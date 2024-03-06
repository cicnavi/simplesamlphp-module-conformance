<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

class Shell
{
    /**
     * Replace ANSI color codes with HTML or other formatting. Update as needed.
     */
    public function replaceColorCodes(string $output): string
    {
        //return $output;
        $colorCodes = [
            '/\e\[(30|0;30)m/' => '<span class="black-text">',
            '/\e\[(31|0;31)m/' => '<span class="red-text">',
            '/\e\[1;31m/' => '<span class="bold-text red-text">',
            '/\e\[(32|0;32)m/' => '<span class="green-text">',
            '/\e\[(33|0;33)m/' => '<span class="yellow-text">',
            '/\e\[(34|0;34)m/' => '<span class="blue-text">',
            '/\e\[(35|0;35)m/' => '<span class="magenta-text">',
            '/\e\[(36|0;36)m/' => '<span class="cyan-text">',
            '/\e\[(37|0;37)m/' => '<span class="white-text">',
            '/\e\[40m/' => '<span class="black-bg">',
            '/\e\[41m/' => '<span class="red-bg">',
            '/\e\[42m/' => '<span class="green-bg;">',
            '/\e\[43m/' => '<span class="yellow-bg;">',
            '/\e\[44m/' => '<span class="blue-bg">',
            '/\e\[45m/' => '<span class="magenta-bg;">',
            '/\e\[46m/' => '<span class="cyan-bg">',
            '/\e\[47m/' => '<span class="white-bg;">',
            '/\e\[1m/' => '<span class="bold-text">',
            '/\e\[4m/' => '<span class="underline-text">',
            '/\e\[5m/' => '<span class="blink-text;">',
            '/\e\[7m/' => '<span class="blue-bg white-text">',
            '/\e\[92m/' => '<span class="green-text">',
            '/\e\[91m/' => '<span class="lightcoral-text">',
            '/\e\[1;92m/' => '<span class="bold-text green-text">',
            '/\e\[93m/' => '<span class="yellow-text">',
            '/\e\[94m/' => '<span class="blue-text">',
            '/\e\[96m/' => '<span class="lightcyan-text">',

            '/\e\[0m/' => '</span>',
        ];

        return preg_replace(array_keys($colorCodes), array_values($colorCodes), $output);
    }
}
