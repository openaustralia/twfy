<?php

/**
 * @file
 * The page for suggesting a glossary term, as a visitor who is signed out first sees it.
 */

require_once __DIR__ . '/../../../PageRenderingIntegrationTestCase.php';

uses(PageRenderingIntegrationTestCase::class);

const ADD_TERM_PAGE = __DIR__ . '/../../../../www/docs/addterm/index.php';

describe('/addterm/ with no phrase yet', function () {
    it('asks for a phrase to search for', function () {
        expect($this->renderPage(ADD_TERM_PAGE)['output'])->toContain('Step 1: Search for a phrase');
    });

    it('does not pass a missing phrase to strtolower()', function () {
        expect($this->renderPage(ADD_TERM_PAGE)['output'])->not->toContain('strtolower()');
    });
});
