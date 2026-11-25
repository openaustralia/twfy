<?php

/**
 * @file
 * Finding a member from /mp/ and /senator/, through Laravel's routes. Uses fictional members, users and postcodes.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\PostcodeLookup;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * Adds a sitting term for a fictional member.
 *
 * @param int $id
 *   The member and person id.
 * @param array<string, int|string> $overrides
 *   Columns to change.
 */
function lookup_term(int $id, array $overrides = []): Member {
    return Member::create($overrides + [
        'member_id' => $id,
        'person_id' => $id,
        'house' => 1,
        'title' => '',
        'first_name' => 'Robin',
        'last_name' => 'Example',
        'constituency' => 'Test Ville',
        'party' => 'Example Party',
        'entered_house' => '2022-05-21',
        'left_house' => '9999-12-31',
        'entered_reason' => 'general_election',
        'left_reason' => 'still_in_office',
    ]);
}

/**
 * Stands in for the visitor.
 *
 * @param string $electorate
 *   The electorate they have given.
 * @param int|null $userId
 *   Their user id, if signed in.
 */
function lookup_visitor(string $electorate = '', ?int $userId = null): void {
    $GLOBALS['THEUSER'] = new class($electorate, $userId) {

        public function __construct(private string $electorate, private ?int $userId) {
        }

        public function constituency(): string {
            return $this->electorate;
        }

        public function isloggedin(): bool {
            return $this->userId !== null;
        }

        public function user_id(): ?int {
            return $this->userId;
        }

    };
}

/**
 * Where a lookup sends the visitor, without the scheme and host.
 */
function lookup_location(string $address): string {
    $location = (string) MemberPageStubs::get($address)->headers->get('Location');
    return (string) preg_replace('#^https?://[^/]*#', '', $location);
}

beforeEach(function () {
    MemberPageStubs::bind();
    $this->theuser = $GLOBALS['THEUSER'] ?? null;
    lookup_visitor();
    lookup_term(97601);
    lookup_term(97602, ['house' => 2, 'first_name' => 'Sam', 'last_name' => 'Sample', 'constituency' => 'Tasmania']);
    PostcodeLookup::create(['postcode' => '9990', 'name' => 'Test Ville']);
});

afterEach(function () {
    $GLOBALS['THEUSER'] = $this->theuser;
    laravel_spec_new_request();
});

describe('GET /mp/ with nothing to go on', function () {
    it('asks for a postcode', function () {
        expect(MemberPageStubs::get('/mp/')->getContent())->toContain('Enter your Australian postcode');
    });

    it('starts no session', function () {
        expect(MemberPageStubs::get('/mp/')->headers->getCookies())->toBe([]);
    });

    dataset('pages', [
        'Representatives' => ['/mp/', '[top:mp:]'],
        'Senators' => ['/senator/', '[top:peer:]'],
    ]);

    it('marks the right menu', function (string $address, string $top) {
        expect(MemberPageStubs::get($address)->getContent())->toContain($top);
    })->with('pages');

    describe('when the electorate is known', function () {
        it('goes to their member', function () {
            lookup_visitor('test ville');

            expect(lookup_location('/mp/'))->toBe('/mp/robin_example/test_ville');
        });
    });
});

describe('GET /mp/ by id', function () {
    dataset('ids', [
        'a person' => ['/mp/?pid=97601', '/mp/robin_example/test_ville'],
        'a person, the old way' => ['/mp/?p=97601', '/mp/robin_example/test_ville'],
        'a term' => ['/mp/?m=97602', '/senator/sam_sample/tasmania'],
        'a Senator' => ['/senator/?pid=97602', '/senator/sam_sample/tasmania'],
    ]);

    it('goes to the profile', function (string $address, string $profile) {
        expect(lookup_location($address))->toBe($profile);
    })->with('ids');

    describe('with an unknown id', function () {
        it('asks for a postcode', function () {
            expect(MemberPageStubs::get('/mp/?pid=97699')->getContent())->toContain('Enter your Australian postcode');
        });
    });
});

describe('GET /mp/ by name or electorate', function () {
    dataset('words', [
        'a name' => ['/mp/?n=robin_example', '/mp/robin_example'],
        'a name and electorate' => ['/mp/?n=robin+example&c=test+ville', '/mp/robin_example/test_ville'],
        'a Senator\'s name' => ['/senator/?n=sam_sample', '/senator/sam_sample'],
        'an electorate' => ['/mp/?c=test_ville', '/mp/robin_example/test_ville'],
    ]);

    it('goes to the profile address', function (string $address, string $profile) {
        expect(lookup_location($address))->toBe($profile);
    })->with('words');
});

describe('GET /mp/?recent=1', function () {
    it('goes to their speeches', function () {
        expect(lookup_location('/mp/?recent=1&pid=97601'))->toBe('/search/?pid=97601&pop=1');
    });

    describe('when the electorate is known', function () {
        it('uses their member', function () {
            lookup_visitor('Test Ville');

            expect(lookup_location('/mp/?recent=1'))->toBe('/search/?pid=97601&pop=1');
        });
    });
});

describe('GET /mp/?pc=', function () {
    it('goes to the Representative', function () {
        expect(lookup_location('/mp/?pc=9990'))->toBe('/mp/robin_example/test_ville');
    });

    it('keeps the electorate in a cookie', function () {
        $cookie = MemberPageStubs::get('/mp/?pc=9990')->headers->getCookies()[0];

        expect([$cookie->getName(), $cookie->getValue()])->toBe([CONSTITUENCY_COOKIE, 'Test Ville']);
    });

    describe('when signed in', function () {
        beforeEach(function () {
            $this->user = User::create([
                'firstname' => 'Lee',
                'lastname' => 'Lookupspec',
                'email' => 'lee.lookupspec@example.test',
                'password' => 'x',
                'constituency' => '',
            ]);
            lookup_visitor('', $this->user->user_id);
        });

        it('saves the electorate', function () {
            MemberPageStubs::get('/mp/?pc=9990');

            expect($this->user->fresh()->constituency)->toBe('Test Ville');
        });

        it('sets no cookie', function () {
            expect(MemberPageStubs::get('/mp/?pc=9990')->headers->getCookies())->toBe([]);
        });
    });

    dataset('bad postcodes', [
        'letters' => ['abc', "abc isn&#039;t a valid postcode"],
        'unknown' => ['9991', "9991 isn&#039;t a known postcode"],
        'markup' => ['%3Cb%3E', "b isn&#039;t a valid postcode"],
    ]);

    it('says what is wrong', function (string $postcode, string $message) {
        expect(MemberPageStubs::get('/mp/?pc=' . $postcode)->getContent())->toContain($message);
    })->with('bad postcodes');

    describe('in several electorates', function () {
        beforeEach(function () {
            PostcodeLookup::create(['postcode' => '9990', 'name' => 'Other Ville']);
        });

        it('asks which', function () {
            expect(MemberPageStubs::get('/mp/?pc=9990')->getContent())
              ->toContain('href="/mp/?pc=9990&amp;c=Other+Ville"', 'Robin Example, Test Ville');
        });

        it('goes to the one chosen', function () {
            expect(lookup_location('/mp/?pc=9990&c=Test+Ville'))->toBe('/mp/robin_example/test_ville');
        });
    });
});
