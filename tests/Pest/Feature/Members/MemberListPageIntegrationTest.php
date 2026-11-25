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
    $this->theuser = $GLOBALS['THEUSER'] ?? null;
    MemberPageStubs::visitor();

    MemberFixtures::term(97101, ['last_name' => "O'Example", 'constituency' => 'Testville']);
    MemberFixtures::term(97102, ['house' => 2, 'first_name' => 'Sam', 'last_name' => 'Sample', 'constituency' => 'Tasmania']);
});

afterEach(function () {
    $GLOBALS['THEUSER'] = $this->theuser;
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
            expect(MemberPageStubs::get('/mps/')->getContent())->toContain('All you need is a postcode.');
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

                expect(MemberPageStubs::get('/mps/')->getContent())->toContain('your former Representative');
            });
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

describe('GET /senators/', function () {
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
