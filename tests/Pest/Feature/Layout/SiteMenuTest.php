<?php

/**
 * @file
 * Who sees the admin hamburger menu in the site menu.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/data.php';
require_once __DIR__ . '/../../../../www/includes/easyparliament/page.php';

// The site menu builds each link with the URL class, which other specs also stand in for.
if (!class_exists('URL')) {
    /**
     * Minimal URL stand-in.
     */
    class URL {

        public function __construct(private string $page) {
        }

        /**
         * @param array<string, string> $params
         *   Query values.
         */
        public function insert(array $params): void {
        }

        public function generate(string $type = ''): string {
            return '/' . $this->page . '/';
        }

    }
}

/**
 * The site menu as a visitor sees it.
 *
 * @param bool $logged_in
 *   Whether they are signed in.
 * @param bool $admin
 *   Whether they may open the admin section.
 */
function site_menu_html(bool $logged_in, bool $admin): string {
    global $this_page, $DATA, $THEUSER;
    $this_page = 'home';
    $DATA = new DATA();
    $THEUSER = new class($logged_in, $admin) {

        public function __construct(private bool $logged_in, private bool $admin) {
        }

        public function isloggedin(): bool {
            return $this->logged_in;
        }

        public function is_able_to(string $action): bool {
            return $this->admin;
        }

        public function firstname(): string {
            return 'Ada';
        }

        public function lastname(): string {
            return 'Example';
        }

        public function __call(string $name, array $args): string {
            return '';
        }

    };

    ob_start();
    (new PAGE())->menu();
    return ob_get_clean();
}

describe('PAGE::menu()', function () {
    it('gives an admin the admin menu', function () {
        expect(site_menu_html(true, true))->toContain('id="admin-menu"');
    });

    it('lists the admin pages in it', function () {
        expect(site_menu_html(true, true))->toContain('href="/admin_api_keys/"');
    });

    it('gives a signed-in non-admin no admin menu', function () {
        expect(site_menu_html(true, false))->not->toContain('admin-menu');
    });

    it('gives a visitor who is signed out no admin menu', function () {
        expect(site_menu_html(false, false))->not->toContain('admin-menu');
    });
});
