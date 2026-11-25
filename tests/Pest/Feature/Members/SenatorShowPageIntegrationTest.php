<?php

/**
 * @file
 * A Senator's profile at /senator/name/state, through Laravel's routes. Uses fictional members.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../Support/MemberFixtures.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Moffice;
use OpenAustralia\TWFY\Models\Personinfo;
use Symfony\Component\HttpFoundation\Response;

uses(TransactionalTestCase::class);

/**
 * The response to a visit.
 */
function senator_page_get(string $address = '/senator/kim_o%27specimen/tasmania'): Response {
    return MemberPageStubs::get($address);
}

beforeEach(function () {
    MemberPageStubs::bind();

    MemberFixtures::term(97401, ['house' => 2, 'first_name' => 'Kim', 'last_name' => "O'Specimen", 'constituency' => 'Tasmania']);
});

afterEach(function () {
    laravel_spec_new_request();
});

describe('GET /senator/{name}/{state}', function () {
    it('titles the page', function () {
        expect(senator_page_get()->getContent())->toContain('[top:peer:Senator Kim O&#039;Specimen, Tasmania]');
    });

    it('heads it with their name', function () {
        expect(senator_page_get()->getContent())->toContain('Senator Kim O&#039;Specimen');
    });

    it('gives their party and state', function () {
        expect(senator_page_get()->getContent())->toContain('Example Party Senator for Tasmania');
    });

    it('offers email alerts', function () {
        expect(senator_page_get()->getContent())->toContain('href="/alert/?only=1&amp;pid=97401"');
    });

    it('starts no session', function () {
        expect(senator_page_get()->headers->getCookies())->toBe([]);
    });

    describe('without a photo', function () {
        it('asks for one', function () {
            expect(senator_page_get()->getContent())->toContain('missing a photo');
        });
    });

    describe('with recent speeches', function () {
        it('links to each', function () {
            MemberPageStubs::speeches([['url' => '/senate/?id=1&s=x', 'title' => 'Motions', 'date' => '14 May 2026', 'extract' => 'I <b>move</b>']]);

            expect(senator_page_get()->getContent())->toContain('href="/senate/?id=1&amp;s=x"', 'I <b>move</b>');
        });
    });

    describe('with the search broken', function () {
        beforeEach(function () {
            MemberPageStubs::speeches(null);
        });

        it('says speeches are unavailable', function () {
            expect(senator_page_get()->getContent())->toContain('Recent appearances are unavailable right now.');
        });

        it('still draws the rest', function () {
            expect(senator_page_get()->getContent())->toContain('id="numbers"', '[bottom]');
        });
    });

    describe('without an interest statement', function () {
        it('says none is available', function () {
            expect(senator_page_get()->getContent())->toContain('entry is not yet available');
        });
    });

    describe('with an interest statement', function () {
        it('links to it', function () {
            Personinfo::create(['person_id' => 97401, 'data_key' => 'aph_interests_url', 'data_value' => 'https://example.test/interests']);

            expect(senator_page_get()->getContent())->toContain('href="https://example.test/interests"');
        });
    });

    describe('with a past office', function () {
        it('lists it', function () {
            Moffice::create(['person' => 97401, 'position' => 'Old Office', 'dept' => '', 'from_date' => '2019-06-01', 'to_date' => '2022-06-01', 'source' => '']);

            expect(senator_page_get()->getContent())->toContain('Other offices held in the past', 'Old Office');
        });
    });

    describe('with a name nobody has', function () {
        it('is not found', function () {
            expect(senator_page_get('/senator/no_body/tasmania')->getStatusCode())->toBe(404);
        });
    });

    describe('with the wrong state', function () {
        it('is not found', function () {
            expect(senator_page_get('/senator/kim_o%27specimen/victoria')->getStatusCode())->toBe(404);
        });
    });

    dataset('plus addresses', [
        'in the name' => ['/senator/kim+o%27specimen'],
        'in both parts' => ['/senator/kim+o%27specimen/new+south+wales'],
        'in the state' => ['/senator/kim_o%27specimen/new+south+wales'],
    ]);

    describe('with "+" for spaces', function () {
        it('redirects to the full address', function (string $address) {
            Member::find(97401)->update(['constituency' => 'New South Wales']);

            expect(senator_page_get($address)->headers->get('Location'))
              ->toEndWith('/senator/kim_o%27specimen/new_south_wales');
        })->with('plus addresses');
    });

    describe('without a state', function () {
        it('redirects to the full address', function () {
            expect(senator_page_get('/senator/kim_o%27specimen')->headers->get('Location'))
              ->toEndWith('/senator/kim_o%27specimen/tasmania');
        });
    });
});
