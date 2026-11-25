<?php

/**
 * @file
 * The Help page, as a visitor who is signed out sees it.
 */

require_once __DIR__ . '/../PageRenderingIntegrationTestCase.php';

uses(PageRenderingIntegrationTestCase::class);

const HELP_PAGE = __DIR__ . '/../../www/docs/help/index.php';

describe('/help/', function () {
    beforeEach(function () {
        $this->html = $this->renderPage(HELP_PAGE)['output'];
    });

    it('lists the glossary question at the top', function () {
        expect($this->html)->toContain('<li><a href="#glossary">What do the terms used in debates mean?</a></li>');
    });

    it('links to the glossary', function () {
        expect($this->html)->toContain('<a href="/glossary/">glossary</a>');
    });

    it('links to the page for suggesting a term', function () {
        expect($this->html)->toContain('<a href="/addterm/">suggest a term</a>');
    });
});
