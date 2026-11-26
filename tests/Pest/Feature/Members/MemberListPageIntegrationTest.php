<?php

/**
 * @file
 * The lists at /mps/ and /senators/, through Laravel's routes. Uses fictional members.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../Support/MemberFixtures.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use OpenAustralia\TWFY\Models\Member;

uses(TransactionalTestCase::class);

if (!defined('DOMAIN')) {
    define('DOMAIN', 'example.test');
}

/**
 * What a download sends.
 */
function member_page_download(string $address): string {
    ob_start();
    MemberPageStubs::get($address)->sendContent();
    return (string) ob_get_clean();
}

beforeEach(function () {
    MemberPageStubs::bind();

    MemberFixtures::term(97101, ['last_name' => "O'Example", 'constituency' => 'Testville']);
    MemberFixtures::term(97102, ['house' => 2, 'first_name' => 'Sam', 'last_name' => 'Sample', 'constituency' => 'Tasmania']);
});

afterEach(function () {
    MemberPageStubs::restoreVisitor();
    laravel_spec_new_request();
});

describe('GET /mps/', function () {
    it('draws the list in the site layout', function () {
        expect(MemberPageStubs::get('/mps/')->getContent())->toContain('[top:mps:]', '[bottom]');
    });

    it('lists a sitting Representative', function () {
        expect(MemberPageStubs::get('/mps/')->getContent())->toContain('Testville');
    });

    it('links to their profile', function () {
        expect(MemberPageStubs::get('/mps/')->getContent())->toContain('href="/mp/robin_o%27example/testville"');
    });

    it('escapes their name', function () {
        expect(MemberPageStubs::get('/mps/')->getContent())->toContain('Robin O&#039;Example');
    });

    it('leaves out Senators', function () {
        expect(MemberPageStubs::get('/mps/')->getContent())->not->toContain('Sam Sample');
    });

    it('offers the other sort orders', function () {
        expect(MemberPageStubs::get('/mps/')->getContent())->toContain('href="/mps?o=f"');
    });

    it('does not link the current order', function () {
        expect(MemberPageStubs::get('/mps/?o=f')->getContent())->not->toContain('href="/mps?o=f"');
    });

    it('starts no session', function () {
        expect(MemberPageStubs::get('/mps/')->headers->getCookies())->toBe([]);
    });

    describe('with a name stored as entities', function () {
        beforeEach(function () {
            Member::find(97101)->update(['last_name' => 'Nu&ntilde;ez']);
        });

        it('shows the characters', function () {
            expect(MemberPageStubs::get('/mps/')->getContent())->toContain('Robin Nuñez')->not->toContain('&amp;ntilde;');
        });

        it('downloads the characters', function () {
            expect(member_page_download('/mps/?f=csv'))->toContain('Nuñez');
        });

        it('links to a profile that opens', function () {
            preg_match('#href="(/mp/robin_nu[^"]+)"#', MemberPageStubs::get('/mps/')->getContent(), $link);

            expect(MemberPageStubs::get($link[1])->getStatusCode())->toBe(200);
        });
    });

    describe('without an electorate', function () {
        it('asks for a postcode', function () {
            expect(MemberPageStubs::get('/mps/')->getContent())->toContain('Enter your postcode to see who represents you.');
        });
    });

    describe('with the visitor\'s electorate', function () {
        beforeEach(function () {
            MemberPageStubs::visitor('Testville');
        });

        it('names their Representative', function () {
            expect(MemberPageStubs::get('/mps/')->getContent())->toContain('is not your Representative');
        });

        describe('when that member has left', function () {
            it('calls them former', function () {
                Member::find(97101)->update(['left_house' => '2025-05-03']);

                expect(MemberPageStubs::get('/mps/')->getContent())->toContain('Your former Representative');
            });
        });
    });

    describe('with a search', function () {
        it('lists who matches', function () {
            expect(MemberPageStubs::get('/mps/?q=testville')->getContent())->toContain('Robin O&#039;Example');
        });

        it('says how many it found', function () {
            expect(MemberPageStubs::get('/mps/?q=testville')->getContent())->toContain('1 Representative<');
        });

        it('keeps the search in the box', function () {
            expect(MemberPageStubs::get('/mps/?q=testville')->getContent())->toContain('value="testville"');
        });

        it('keeps the search in the sort links', function () {
            expect(MemberPageStubs::get('/mps/?q=testville')->getContent())->toContain('href="/mps?o=f&amp;q=testville"');
        });

        it('keeps the search in the spreadsheet link', function () {
            expect(MemberPageStubs::get('/mps/?q=testville')->getContent())->toContain('href="/mps?f=csv&amp;q=testville"');
        });

        it('offers to clear it', function () {
            expect(MemberPageStubs::get('/mps/?q=testville')->getContent())->toContain('Clear filters');
        });

        describe('for a name stored with an accent', function () {
            it('finds them by the plain text', function () {
                Member::find(97101)->update(['last_name' => 'Gon&ccedil;alves']);

                expect(MemberPageStubs::get('/mps/?q=' . urlencode('gonçalves'))->getContent())->toContain('Robin Gonçalves');
            });
        });

        describe('that matches nobody', function () {
            it('says so', function () {
                expect(MemberPageStubs::get('/mps/?q=nobody')->getContent())->toContain('No representatives match your search.');
            });
        });

        describe('that holds markup', function () {
            it('shows it as text', function () {
                $html = MemberPageStubs::get('/mps/?q=' . urlencode('"><script>alert(1)</script>'))->getContent();

                expect($html)->toContain('value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"')->not->toContain('<script>alert(1)');
            });
        });

        describe('that is too long', function () {
            it('goes back to the plain list', function () {
                expect(MemberPageStubs::get('/mps/?q=' . str_repeat('a', 101))->getStatusCode())->toBe(302);
            });
        });

        describe('that is not text', function () {
            it('goes back to the plain list', function () {
                expect(MemberPageStubs::get('/mps/?q[]=a')->headers->get('Location'))->toEndWith('/mps');
            });
        });
    });

    describe('with a party', function () {
        it('offers each party', function () {
            expect(MemberPageStubs::get('/mps/')->getContent())->toContain('>Example Party</option>');
        });

        it('leaves out anyone else', function () {
            MemberFixtures::term(97103, ['first_name' => 'Lee', 'last_name' => 'Other', 'constituency' => 'Elsewhere', 'party' => 'Other Party']);

            expect(MemberPageStubs::get('/mps/?party=' . urlencode('Other Party'))->getContent())->toContain('Lee Other')->not->toContain('Testville');
        });

        it('marks it chosen', function () {
            expect(MemberPageStubs::get('/mps/?party=' . urlencode('Example Party'))->getContent())->toMatch('/<option value="Example Party"\\s+selected>/');
        });
    });

    describe('with a sort and a search', function () {
        it('keeps the sort when searching again', function () {
            expect(MemberPageStubs::get('/mps/?o=p')->getContent())->toContain('<input type="hidden" name="o" value="p">');
        });
    });

    describe('with ?f=csv', function () {
        it('downloads a named spreadsheet', function () {
            expect(MemberPageStubs::get('/mps/?f=csv')->headers->get('Content-Disposition'))->toContain('representatives.csv');
        });

        it('has a row for each member', function () {
            expect(member_page_download('/mps/?f=csv'))
              ->toContain("97101,Robin,O'Example,\"Example Party\",Testville,https://" . DOMAIN . '/mp/robin_o%27example/testville');
        });
    });
});

describe('the list routes', function () {
    dataset('lists', ['/mps', '/senators']);

    it('answer only to GET and HEAD', function (string $uri) {
        $methods = collect(app('router')->getRoutes()->getRoutes())
          ->filter(fn ($route) => $route->uri() === ltrim($uri, '/'))
          ->flatMap(fn ($route) => $route->methods())
          ->unique()
          ->sort()
          ->values()
          ->all();

        expect($methods)->toBe(['GET', 'HEAD']);
    })->with('lists');
});

describe('GET /senators/', function () {
    it('has no postcode finder', function () {
        expect(MemberPageStubs::get('/senators/')->getContent())->not->toContain('name="pc"');
    });

    it('marks the Senators menu', function () {
        expect(MemberPageStubs::get('/senators/')->getContent())->toContain('[top:peers:]');
    });

    it('links to a Senator\'s profile', function () {
        expect(MemberPageStubs::get('/senators/')->getContent())->toContain('href="/senator/sam_sample/tasmania"');
    });

    it('leaves out Representatives', function () {
        expect(MemberPageStubs::get('/senators/')->getContent())->not->toContain('Testville');
    });

    it('heads the seat column "State"', function () {
        expect(MemberPageStubs::get('/senators/')->getContent())->toContain('>State</a>');
    });

    describe('with ?f=csv', function () {
        it('has a row for each Senator', function () {
            expect(member_page_download('/senators/?f=csv'))->toContain('97102,"Sam Sample","Example Party",https://');
        });
    });
});
