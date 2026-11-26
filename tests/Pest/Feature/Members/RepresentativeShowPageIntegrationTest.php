<?php

/**
 * @file
 * A Representative's profile at /mp/name/electorate, through Laravel's routes. Uses fictional members.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../Support/MemberFixtures.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use OpenAustralia\TWFY\Models\Member;
use Symfony\Component\HttpFoundation\Response;

uses(TransactionalTestCase::class);

/**
 * The response to a visit.
 */
function representative_page_get(string $address = '/mp/robin_o%27example/test_ville'): Response {
    return MemberPageStubs::get($address);
}

/**
 * Adds a term for the fictional Representative these specs are about.
 *
 * @param int $id
 *   The member and person id.
 * @param array<string, int|string> $overrides
 *   Columns to change.
 */
function representative_page_term(int $id, array $overrides = []): Member {
    return MemberFixtures::term($id, $overrides + ['last_name' => "O'Example"]);
}

beforeEach(function () {
    MemberPageStubs::bind();
    representative_page_term(97501);
});

afterEach(function () {
    MemberPageStubs::restoreVisitor();
    laravel_spec_new_request();
});

describe('GET /mp/{name}/{electorate}', function () {
    it('titles the page', function () {
        expect(representative_page_get()->getContent())->toContain('[top:mp:Robin O&#039;Example MP, Test Ville]');
    });

    it('gives their party and electorate', function () {
        expect(representative_page_get()->getContent())->toContain('Example Party</dd>', 'Test Ville</dd>');
    });

    it('is teal', function () {
        expect(representative_page_get()->getContent())->toContain('bg-teal-900');
    });

    it('names their house', function () {
        expect(representative_page_get()->getContent())->toContain('>House of Representatives</span>');
    });

    it('starts no session', function () {
        expect(representative_page_get()->headers->getCookies())->toBe([]);
    });

    describe('in the visitor\'s electorate', function () {
        it('marks the visitor\'s menu entry', function () {
            MemberPageStubs::visitor('test ville');

            expect(representative_page_get()->getContent())->toContain('[top:yourmp:');
        });
    });

    describe('with the wrong electorate', function () {
        it('redirects to the right one', function () {
            expect(representative_page_get('/mp/robin_o%27example/elsewhere')->headers->get('Location'))
              ->toEndWith('/mp/robin_o%27example/test_ville');
        });
    });

    describe('with a name nobody has', function () {
        it('is not found', function () {
            expect(representative_page_get('/mp/no_body/test_ville')->getStatusCode())->toBe(404);
        });
    });
});

describe('GET /mp/{name}', function () {
    dataset('old addresses', [
        'a name alone' => ['/mp/robin_o%27example'],
        'a trailing slash' => ['/mp/robin_o%27example/'],
        'a trailing " mp"' => ['/mp/robin%20o%27example%20mp'],
        'a "+" for a space' => ['/mp/robin+o%27example'],
        'a "+" before "mp"' => ['/mp/robin+o%27example+mp'],
        'a "+" in the electorate' => ['/mp/robin_o%27example/test+ville'],
        'a "+" in both' => ['/mp/robin+o%27example/test+ville'],
    ]);

    it('redirects to the full address', function (string $address) {
        expect(representative_page_get($address)->headers->get('Location'))->toEndWith('/mp/robin_o%27example/test_ville');
    })->with('old addresses');

    describe('with a name two people share', function () {
        beforeEach(function () {
            representative_page_term(97502, ['constituency' => 'Other Ville', 'entered_house' => '2001-11-10', 'left_house' => '2004-08-31']);
        });

        it('lists each of them', function () {
            expect(representative_page_get('/mp/robin_o%27example')->getContent())
              ->toContain('href="/mp/?pid=97501"', 'href="/mp/?pid=97502"');
        });

        it('shows one when given a seat', function () {
            expect(representative_page_get()->getContent())->toContain('Test Ville</dd>');
        });
    });
});
