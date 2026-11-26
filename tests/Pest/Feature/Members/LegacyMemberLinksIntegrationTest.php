<?php

/**
 * @file
 * The links the legacy profile page draws in its sidebar. Uses a fictional person.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../../../www/includes/data.php';
require_once __DIR__ . '/../../../../www/includes/easyparliament/page.php';
require_once __DIR__ . '/../../Support/MemberFixtures.php';

use OpenAustralia\TWFY\Models\Personinfo;

uses(TransactionalTestCase::class);

/**
 * What the legacy page puts in its sidebar for the person.
 *
 * @param array<string, string> $links
 *   The legacy page's facts about the person.
 */
function legacy_member_links(array $links = []): string {
    $member = new class() {

        public function person_id(): int {
            return 97501;
        }

    };

    return (new PAGE())->generate_member_links($member, $links);
}

describe('PAGE::generate_member_links()', function () {
    beforeEach(function () {
        MemberFixtures::term(97501);
        Personinfo::create(['person_id' => 97501, 'data_key' => 'mp_contact_form', 'data_value' => 'https://example.test/contact']);
    });

    it('draws the block heading', function () {
        expect(legacy_member_links())->toContain('More useful links for this person');
    });

    it('lists the links the profile has', function () {
        expect(legacy_member_links())->toContain('<a href="https://example.test/contact">Contact form</a>');
    });

    describe('with a maiden speech', function () {
        it('links to the debate', function () {
            expect(legacy_member_links(['maiden_speech' => 'uk.org.publicwhip/debate/2022-07-26.1.1']))
              ->toContain('<a href="/debate/?id=2022-07-26.1.1">Maiden speech</a>');
        });
    });

    describe('without a maiden speech', function () {
        it('leaves the link out', function () {
            expect(legacy_member_links())->not->toContain('Maiden speech');
        });
    });
});
