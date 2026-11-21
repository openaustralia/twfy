<?php

/**
 * @file
 * The notice above a day of Hansard that is only a proof.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use League\Plates\Engine;

describe('hansard/proof-notice', function () {
    beforeEach(function () {
        $this->html = (new Engine(__DIR__ . '/../../www/resources/views'))->render('hansard/proof-notice');
    });

    it('draws its border', function () {
        expect($this->html)->toContain('border border-solid border-amber-300');
    });

    it('is a note for screen readers', function () {
        expect($this->html)->toContain('role="note"');
    });
});
