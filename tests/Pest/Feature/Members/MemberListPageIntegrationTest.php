<?php

/**
 * @file
 * The lists at /mps/ and /senators/, through Laravel's routes. Uses fictional members.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Http\LegacyLayout;
use OpenAustralia\TWFY\Models\Member;
use Symfony\Component\HttpFoundation\Response;

uses(TransactionalTestCase::class);

if (!defined('DOMAIN')) {
    define('DOMAIN', 'example.test');
}

/**
 * The response to a visit.
 */
function member_page_get(string $address): Response {
    return twfy_laravel()->make(Kernel::class)->handle(Request::create($address));
}

/**
 * What a download sends.
 */
function member_page_download(string $address): string {
    ob_start();
    member_page_get($address)->sendContent();
    return (string) ob_get_clean();
}

/**
 * Stands in for the visitor, who may have given their electorate.
 */
function member_page_visitor(string $electorate = ''): void {
    $GLOBALS['THEUSER'] = new class($electorate) {

        public function __construct(private string $electorate) {
        }

        public function constituency(): string {
            return $this->electorate;
        }

    };
}

beforeEach(function () {
    // The legacy page layout isn't loaded in tests, so stand in for it.
    twfy_laravel()->bind(LegacyLayout::class, fn () => new class extends LegacyLayout {

        public function top(string $page, array $metadata = []): string {
            return "[top:$page]";
        }

        public function bottom(): string {
            return '[bottom]';
        }

    });
    $this->theuser = $GLOBALS['THEUSER'] ?? null;
    member_page_visitor();

    $term = [
        'title' => '',
        'party' => 'Example Party',
        'entered_house' => '2022-05-21',
        'left_house' => '9999-12-31',
        'entered_reason' => 'general_election',
        'left_reason' => 'still_in_office',
    ];
    Member::create($term + ['member_id' => 97101, 'person_id' => 97101, 'house' => 1, 'first_name' => 'Robin', 'last_name' => "O'Example", 'constituency' => 'Testville']);
    Member::create($term + ['member_id' => 97102, 'person_id' => 97102, 'house' => 2, 'first_name' => 'Sam', 'last_name' => 'Sample', 'constituency' => 'Tasmania']);
});

afterEach(function () {
    $GLOBALS['THEUSER'] = $this->theuser;
    laravel_spec_new_request();
});

describe('GET /mps/', function () {
    it('draws the list in the site layout', function () {
        expect(member_page_get('/mps/')->getContent())->toContain('[top:mps]', '[bottom]');
    });

    it('lists a sitting Representative', function () {
        expect(member_page_get('/mps/')->getContent())->toContain('Testville');
    });

    it('links to their profile', function () {
        expect(member_page_get('/mps/')->getContent())->toContain('href="/mp/robin_o%27example/testville"');
    });

    it('escapes their name', function () {
        expect(member_page_get('/mps/')->getContent())->toContain('Robin O&#039;Example');
    });

    it('leaves out Senators', function () {
        expect(member_page_get('/mps/')->getContent())->not->toContain('Sam Sample');
    });

    it('offers the other sort orders', function () {
        expect(member_page_get('/mps/')->getContent())->toContain('href="/mps?o=f"');
    });

    it('does not link the current order', function () {
        expect(member_page_get('/mps/?o=f')->getContent())->not->toContain('href="/mps?o=f"');
    });

    it('starts no session', function () {
        expect(member_page_get('/mps/')->headers->getCookies())->toBe([]);
    });

    describe('without an electorate', function () {
        it('asks for a postcode', function () {
            expect(member_page_get('/mps/')->getContent())->toContain('All you need is a postcode.');
        });
    });

    describe('with the visitor\'s electorate', function () {
        beforeEach(function () {
            member_page_visitor('Testville');
        });

        it('names their Representative', function () {
            expect(member_page_get('/mps/')->getContent())->toContain('is not your Representative');
        });

        describe('when that member has left', function () {
            it('calls them former', function () {
                Member::find(97101)->update(['left_house' => '2025-05-03']);

                expect(member_page_get('/mps/')->getContent())->toContain('your former Representative');
            });
        });
    });

    describe('with ?f=csv', function () {
        it('downloads a named spreadsheet', function () {
            expect(member_page_get('/mps/?f=csv')->headers->get('Content-Disposition'))->toContain('representatives.csv');
        });

        it('has a row for each member', function () {
            expect(member_page_download('/mps/?f=csv'))
              ->toContain("97101,Robin,O'Example,\"Example Party\",Testville,https://" . DOMAIN . '/mp/robin_o%27example/testville');
        });
    });
});

describe('GET /senators/', function () {
    it('marks the Senators menu', function () {
        expect(member_page_get('/senators/')->getContent())->toContain('[top:peers]');
    });

    it('links to a Senator\'s profile', function () {
        expect(member_page_get('/senators/')->getContent())->toContain('href="/senator/sam_sample/tasmania"');
    });

    it('leaves out Representatives', function () {
        expect(member_page_get('/senators/')->getContent())->not->toContain('Testville');
    });

    it('heads the seat column "State"', function () {
        expect(member_page_get('/senators/')->getContent())->toContain('>State</a>');
    });

    describe('with ?f=csv', function () {
        it('has a row for each Senator', function () {
            expect(member_page_download('/senators/?f=csv'))->toContain('97102,"Sam Sample","Example Party",https://');
        });
    });
});
