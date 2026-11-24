<?php

/**
 * @file
 * Every Blade view and component compiles to PHP that parses, so a bad directive fails here and not on a live page.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

/**
 * The Blade files under www/resources/views, by path relative to the views folder.
 *
 * @return array<string, array{string}>
 *   One dataset entry for each file.
 */
function blade_view_files(): array {
    $root = realpath(__DIR__ . '/../../../../../www/resources/views');
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        if (str_ends_with((string) $file, '.blade.php')) {
            $files[substr((string) $file, strlen($root) + 1)] = [(string) $file];
        }
    }
    return $files;
}

describe('a Blade view', function () {
    it('compiles to valid PHP', function (string $path) {
        $compiled = twfy_laravel()->make('blade.compiler')->compileString((string) file_get_contents($path));

        expect(fn () => token_get_all($compiled, TOKEN_PARSE))->not->toThrow(ParseError::class);
    })->with(blade_view_files());
});
