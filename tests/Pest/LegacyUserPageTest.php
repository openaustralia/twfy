<?php

/**
 * @file
 * Where the old /user/ address sends people to their details.
 */

use OpenAustralia\TWFY\Account\LegacyUserPage;

describe('LegacyUserPage::destination()', function () {
    it('sends people to the new page', function (string $pg, string $expected) {
        expect(LegacyUserPage::destination($pg))->toBe($expected);
    })->with([
        'no pg' => ['', 'account/'],
        'edit' => ['edit', 'account/edit/'],
        'editother' => ['editother', 'account/edit/'],
        'anything else' => ['anything', 'account/'],
    ]);
});
