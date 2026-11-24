<?php

/**
 * @file
 * The admin hamburger menu in the site menu.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

beforeEach(function () {
    $this->view = $this->view('layout.admin-menu', [
'sections' => [
        ['heading' => null, 'links' => [['title' => 'Home', 'url' => '/admin/', 'icon' => 'home', 'current' => true]]],
        ['heading' => 'Reports', 'links' => [['title' => 'Searches <b>', 'url' => '/admin/searches.php?a=1&b=2', 'icon' => 'search', 'current' => false]]],
        ['heading' => 'Admin', 'links' => [['title' => 'Users', 'url' => '/admin/users.php', 'icon' => 'users', 'current' => false]]],
    ]
]);
});

describe('layout.admin-menu', function () {
    it('names the menu for screen readers', function () {
        $this->view->assertSee('aria-label="Admin menu"', false);
    });

    it('links each admin page', function () {
        $this->view->assertSee('href="/admin/searches.php?a=1&amp;b=2"', false);
    });

    it('escapes a title', function () {
        $this->view->assertSee('Searches &lt;b&gt;', false);
    });

    it('marks the page you are on', function () {
        $this->view->assertSee('aria-current="page"', false);
    });

    it('marks only that page', function () {
        expect(substr_count((string) $this->view, 'aria-current="page"'))->toBe(1);
    });

    it('groups the reports', function () {
        $this->view->assertSeeInOrder(['Reports', 'Searches', 'Admin', 'Users']);
    });

    it('gives each page an icon that screen readers skip', function () {
        expect(substr_count((string) $this->view, 'aria-hidden="true"'))->toBe(4);
    });

    it('closes when you click elsewhere', function () {
        $this->view->assertSee("removeAttribute('open')", false);
    });
});
