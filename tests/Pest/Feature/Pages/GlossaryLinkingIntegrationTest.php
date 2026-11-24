<?php

/**
 * @file
 * The links between glossary terms on a term page, from the real page in its own process. Uses fictional people and terms.
 */

require_once __DIR__ . '/../../../PageRenderingIntegrationTestCase.php';

use OpenAustralia\TWFY\Models\Editqueue;
use OpenAustralia\TWFY\Models\Glossary;
use OpenAustralia\TWFY\Models\User;

uses(PageRenderingIntegrationTestCase::class);

const GLOSSARY_PAGE = __DIR__ . '/../../../../www/docs/glossary/index.php';

beforeEach(function () {
    $this->user = User::create(['firstname' => 'Ada', 'lastname' => 'Example', 'email' => 'linking' . random_int(1000, 9999) . '@example.test']);
    $this->terms = new ArrayObject();
});

afterEach(function () {
    foreach ($this->terms as $term) {
        Editqueue::where('glossary_id', $term->glossary_id)->delete();
        $term->delete();
    }
    $this->user->delete();
});

/**
 * Adds an approved term, which the page's own process can see because it is committed.
 */
function linking_term(string $title, string $body): Glossary {
    $user = test()->user;
    $term = Glossary::create(['title' => $title, 'body' => $body, 'type' => 2, 'visible' => true]);
    Editqueue::create(['user_id' => $user->user_id, 'edit_type' => 2, 'glossary_id' => $term->glossary_id, 'title' => $title, 'body' => $body, 'submitted' => '2026-11-01 10:00:00', 'approved' => true]);
    test()->terms->append($term);
    return $term;
}

describe('/glossary/ for a body that names terms', function () {
    beforeEach(function () {
        linking_term('Quorum', 'A minimum number of members.');
        $term = linking_term('Filibuster', 'A Filibuster is a long speech, often used to block a Quorum.');
        $this->html = $this->renderPage(GLOSSARY_PAGE, 'GET', ['gl' => (string) $term->glossary_id])['output'];
    });

    it('links another term', function () {
        expect($this->html)->toContain('class="glossary">Quorum</a>');
    });

    it('does not link the term to itself', function () {
        expect($this->html)->not->toContain('class="glossary">Filibuster</a>');
    });

    it('marks the term in bold in its own definition', function () {
        expect($this->html)->toContain('<strong>Filibuster</strong>');
    });
});

describe('/glossary/ for a term whose title holds markup', function () {
    beforeEach(function () {
        $term = linking_term('</title><script>alert(1)</script>', 'A body.');
        $this->html = $this->renderPage(GLOSSARY_PAGE, 'GET', ['gl' => (string) $term->glossary_id])['output'];
    });

    it('draws no script from the title', function () {
        expect($this->html)->not->toContain('<script>alert(1)');
    });

    it('keeps the page title whole', function () {
        expect($this->html)->toContain('<title>&lt;/title&gt;&lt;script&gt;alert(1)&lt;/script&gt;: Glossary item');
    });
});
