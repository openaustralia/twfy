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
 * @param string $page
 *   The page key of the page being shown.
 * @param \DATA|null $data
 *   The page metadata, if not the real thing.
 */
function site_menu_html(bool $logged_in, bool $admin, string $page = 'home', ?DATA $data = null): string {
    global $this_page, $DATA, $THEUSER;
    $this_page = $page;
    $DATA = $data ?? new DATA();
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

    it('marks the Import status page as the current one', function () {
        expect(substr_count(site_menu_html(true, true, 'admin_proof'), 'aria-current="page"'))->toBe(1);
    });

    it('marks no admin page as current on a page below Import status', function () {
        expect(substr_count(site_menu_html(true, true, 'admin_proof_year'), 'aria-current="page"'))->toBe(0);
    });

    dataset('member pages', [
        'the Senators list' => ['peers', '/peers/', 'border-rose-400'],
        'a Senator' => ['peer', '/peers/', 'border-rose-400'],
        'a Representative' => ['mp', '/mps/', 'border-teal-400'],
        'the visitor\'s Representative' => ['yourmp', '/mps/', 'border-teal-400'],
    ]);

    it('underlines the section', function (string $page, string $link, string $colour) {
        preg_match('#<a href="' . $link . '"[^>]*>#', site_menu_html(false, false, $page), $tab);

        expect($tab[0])->toContain($colour, 'aria-current="page"');
    })->with('member pages');

    describe('when the admin menu fails', function () {
        beforeEach(function () {
            $this->data = new class extends DATA {

                public function page_metadata($page, $key) {
                    if (str_starts_with((string) $page, 'admin_')) {
                        throw new RuntimeException('No metadata for ' . $page);
                    }
                    return parent::page_metadata($page, $key);
                }

            };
        });

        it('still draws the site menu', function () {
            expect(site_menu_html(true, true, 'home', $this->data))->toContain('id="menu"');
        });

        it('tells the admin the menu is unavailable', function () {
            expect(site_menu_html(true, true, 'home', $this->data))->toContain('Admin menu unavailable');
        });
    });
});

describe('the admin pages in the menu', function () {
    it('each have a page file', function (string $page) {
        $url = (new DATA())->page_metadata($page, 'url');

        expect(file_exists(__DIR__ . '/../../../../www/docs/' . $url))->toBeTrue();
    })->with(['admin_home', 'admin_searchlogs', 'admin_popularsearches', 'admin_failedsearches', 'admin_statistics', 'admin_api_keys', 'admin_downloads', 'admin_proof', 'admin_users', 'admin_alerts', 'admin_glossary', 'admin_glossary_pending']);
});
