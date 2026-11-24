<?php

/**
 * @file
 * The recent written answers and statements templates, from the list's rows to the HTML.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/LegacyTemplate.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../../../www/includes/easyparliament/member.php';

$answers = [
'info' => '',
'data' => [[
    'body' => 'Fisheries',
    'hdate' => '2004-03-24',
    'list_url' => '/wrans/?id=2004-03-24.342W.234',
    'child' => ['body' => 'To ask the Minister.', 'speaker' => []],
    'parent' => ['body' => 'Environment'],
]
]
];

describe('html/hansard_recent_wrans', function () use ($answers) {
    beforeEach(function () use ($answers) {
        $this->html = LegacyTemplate::render('html/hansard_recent_wrans', $answers);
    });

    it('shows the section and the question', function () {
        expect($this->html)->toContain('Environment: Fisheries');
    });

    it('shows the date', function () {
        expect($this->html)->toContain('<small>(24 March 2004)</small>');
    });
});

describe('html/hansard_recent_wms', function () use ($answers) {
    beforeEach(function () use ($answers) {
        $this->html = LegacyTemplate::render('html/hansard_recent_wms', $answers);
    });

    it('shows the section and the statement', function () {
        expect($this->html)->toContain('Environment: Fisheries');
    });

    it('shows the date', function () {
        expect($this->html)->toContain('24 March 2004</dt>');
    });
});
