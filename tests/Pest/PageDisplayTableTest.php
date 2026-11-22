<?php

/**
 * @file
 * The legacy table helper the older admin pages use.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/data.php';
require_once __DIR__ . '/../../www/includes/easyparliament/skin.php';
require_once __DIR__ . '/../../www/includes/easyparliament/page.php';

describe('PAGE::display_table()', function () {
    it('prints the shared table', function () {
        ob_start();
        (new PAGE())->display_table(['header' => ['Query', 'Count'], 'rows' => [['budget', '12']]]);

        expect(ob_get_clean())->toContain('<td class="px-3 py-2 text-right tabular-nums">12</td>');
    });

    it('keeps the table off the edge', function () {
        ob_start();
        (new PAGE())->display_table(['header' => ['Query'], 'rows' => [['budget']]]);

        expect(ob_get_clean())->toStartWith('<div class="mx-4">');
    });
});
