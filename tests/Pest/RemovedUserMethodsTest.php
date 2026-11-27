<?php

/**
 * @file
 * Nothing under www/ may call what the legacy USER class no longer has.
 */

/**
 * The PHP files under www/ that mention a call.
 *
 * Skips docs/mp/mobile.php, which still calls them and is being removed (openaustralia/openaustralia#943).
 *
 * @return list<string>
 *   Their paths, relative to www/.
 */
function files_calling(string $call): array {
    $www = realpath(__DIR__ . '/../../www');
    $found = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($www, FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = $file->getPathname();
        if ($file->getExtension() !== 'php' || str_ends_with($path, 'docs/mp/mobile.php')) {
            continue;
        }
        if (str_contains((string) file_get_contents($path), $call)) {
            $found[] = substr($path, strlen($www) + 1);
        }
    }

    return $found;
}

describe('the legacy USER class', function () {
    it('is not asked for what it no longer has', function (string $call) {
        expect(files_calling($call))->toBe([]);
    })->with(['update_self' => '->update_self(', 'emailpublic' => '->emailpublic(', 'url' => '$THEUSER->url(']);
});
