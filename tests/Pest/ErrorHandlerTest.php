<?php

/**
 * @file
 * The site's error handler, called as Sentry's does: from strict-typed code, with the error number as an int.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/utility.php';

describe('error_handler()', function () {
    it('takes the error number as an int', function () {
        expect(@error_handler(E_WARNING, 'unserialize(): Error at offset 0', __FILE__, 1))->toBeFalse();
    });

    it('skips an error that @ suppressed', function () {
        ob_start();
        @error_handler(E_WARNING, 'unserialize(): Error at offset 0', __FILE__, 1);

        expect(ob_get_clean())->toBe('');
    });
});
