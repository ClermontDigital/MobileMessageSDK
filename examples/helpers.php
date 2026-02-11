<?php

/**
 * Shared helper functions for Mobile Message SDK examples
 */

function loadEnv(string $path): void {
    if (!file_exists($path)) {
        echo "Error: .env file not found. Please copy .env.example to .env and add your credentials.\n";
        exit(1);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;

        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}
