<?php

/**
 * @file
 * Legacy pages can't declare functions that Laravel's global helpers already define, as PHP stops with a fatal error.
 */

/**
 * The functions the legacy code declares at the top level of a file, that a vendor package has already defined.
 *
 * @return list<string>
 *   Each as "name (file)".
 */
function laravel_helper_clashes(): array {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $root = realpath(__DIR__ . '/../..');
    $clashes = [];

    foreach (['www', 'scripts'] as $directory) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!str_ends_with((string) $file, '.php')) {
                continue;
            }
            preg_match_all('/^function\s+(\w+)\s*\(/m', (string) file_get_contents((string) $file), $matches);
            foreach ($matches[1] as $name) {
                if (function_exists($name) && str_contains((new ReflectionFunction($name))->getFileName() ?: '', '/vendor/')) {
                    $clashes[] = $name . ' (' . substr((string) $file, strlen($root) + 1) . ')';
                }
            }
        }
    }

    return $clashes;
}

describe('the legacy pages and scripts', function () {
    it('redeclare no vendor function', function () {
        expect(laravel_helper_clashes())->toBe([]);
    });
});
