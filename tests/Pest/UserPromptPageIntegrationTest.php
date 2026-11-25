<?php

/**
 * @file
 * The page that asks a signed-out visitor to sign in before suggesting a glossary term.
 */

require_once __DIR__ . '/../PageRenderingIntegrationTestCase.php';

uses(PageRenderingIntegrationTestCase::class);

const USER_PROMPT_PAGE = __DIR__ . '/../../www/docs/user/prompt/index.php';

describe('/user/prompt/ for a glossary term', function () {
    beforeEach(function () {
        $this->html = $this->renderPage(USER_PROMPT_PAGE, 'GET', ['type' => '2', 'g' => 'point of order'])['output'];
    });

    it('returns to the add term page with the phrase after signing in', function () {
        expect($this->html)->toContain('name="ret" value="/addterm/?g=point+of+order"');
    });

    it('returns there after joining too', function () {
        expect($this->html)->toContain('ret=%2Faddterm%2F%3Fg%3Dpoint%2Bof%2Border');
    });
});
