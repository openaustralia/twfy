<?php

/**
 * @file
 * Finding a Scottish Parliament written answer by its ID. Uses fictional gids and dates.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/utility.php';
require_once __DIR__ . '/../../www/includes/easyparliament/hansardlist.php';

use OpenAustralia\TWFY\Models\Hansard;

uses(TransactionalTestCase::class);

describe('SPWRANSLIST::get_gid_from_spid()', function () {
    beforeEach(function () {
        Hansard::create([
            'epobject_id' => 920001,
            'gid' => 'uk.org.publicwhip/spwa/2026-03-01.S1W-12345.h',
            'major' => 8,
            'hdate' => '2026-03-01',
        ]);
        $this->list = new SPWRANSLIST();
    });

    describe('with an ID that exists', function () {
        it('returns the gid without its prefix', function () {
            expect($this->list->get_gid_from_spid('S1W-12345'))->toBe('2026-03-01.S1W-12345.h');
        });
    });

    describe('with an ID that does not exist', function () {
        it('returns null', function () {
            expect($this->list->get_gid_from_spid('S1W-99999'))->toBeNull();
        });
    });

    describe('with SQL in the ID', function () {
        it('returns null', function (string $spid) {
            expect($this->list->get_gid_from_spid($spid))->toBeNull();
        })->with([
            'a closed quote and a comment' => ["' OR 1=1 -- "],
            'a UNION SELECT' => ["' UNION SELECT 'uk.org.publicwhip/spwa/2026-03-01.S1W-12345.h' -- "],
        ]);
    });
});
