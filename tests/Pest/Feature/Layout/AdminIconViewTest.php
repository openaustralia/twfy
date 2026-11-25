<?php

/**
 * @file
 * The admin menu's icons.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

describe('the admin icon component', function () {
    it('draws a path for each icon', function (string $name) {
        expect((string) $this->blade('<x-admin.icon name="' . $name . '" />'))->toContain('<path');
    })->with(['home', 'search', 'popular', 'failed', 'statistics', 'key', 'download', 'sync', 'users', 'bell', 'book', 'clock']);

    it('is the size asked for', function () {
        $this->blade('<x-admin.icon name="home" :size="32" />')->assertSee('width="32"', false);
    });
});
