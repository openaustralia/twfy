<?php

/**
 * @file
 * The title of an admin page.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

describe('layout.admin-title', function () {
    it('shows the title escaped', function () {
        $this->view('layout.admin-title', ['title' => 'Users <b>', 'icon' => 'users'])->assertSee('Users &lt;b&gt;', false);
    });

    it('shows the icon', function () {
        $this->view('layout.admin-title', ['title' => 'Users', 'icon' => 'users'])->assertSee('<svg', false);
    });

    it('leaves out the icon for a page without one', function () {
        $this->view('layout.admin-title', ['title' => 'Users', 'icon' => ''])->assertDontSee('<svg', false);
    });
});
