<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('logInfo')) {
    function logInfo(string $text, string $color = 'white', $context = []): void
    {
        $colors = [
            'red'     => "\033[31m",
            'green'   => "\033[32m",
            'yellow'  => "\033[33m",
            'blue'    => "\033[34m",
            'magenta' => "\033[35m",
            'cyan'    => "\033[36m",
            'white'   => "\033[37m",
            'orange'  => "\033[38;5;208m",
            'purple'  => "\033[38;5;129m",
        ];

        $reset = "\033[0m";
        $colorCode = $colors[$color] ?? $colors['white'];
        Log::withoutContext()->info($colorCode . $text . $reset, $context);
    }
}
