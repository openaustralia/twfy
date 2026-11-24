<?php

/**
 * @file
 * The public glossary pages, from the database to the HTML. Uses fictional people and terms.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';
require_once __DIR__ . '/../../Support/UrlStandIn.php';
require_once __DIR__ . '/../../../../www/includes/data.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../../../www/includes/lib_filter.php';
require_once __DIR__ . '/../../../../www/includes/easyparliament/glossary.php';

use OpenAustralia\TWFY\Models\Editqueue;
use OpenAustralia\TWFY\Models\Glossary;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Pages\GlossaryController;

uses(AdminControllerTestCase::class);

/**
 * An approved term from a fictional person.
 */
function public_glossary_term(string $title, string $body = 'A term.', string $first = 'Ada'): Glossary {
    $user = User::create(['firstname' => $first, 'lastname' => '<Example>', 'email' => 'ada' . random_int(1000, 9999) . '@example.test']);
    $term = Glossary::create(['title' => $title, 'body' => $body, 'type' => 2, 'visible' => true]);
    Editqueue::create(['user_id' => $user->user_id, 'edit_type' => 2, 'glossary_id' => $term->glossary_id, 'title' => $title, 'body' => $body, 'submitted' => '2026-11-01 10:00:00', 'approved' => true]);
    return $term;
}

beforeEach(function () {
    $GLOBALS['DATA'] = new DATA();
    // lib_filter.php makes this at the top of the file, which is not the global scope when a spec loads it.
    $GLOBALS['filter'] = new lib_filter();
});

describe('GlossaryController::index() for a letter', function () {
    beforeEach(function () {
        public_glossary_term('Quorum');
        public_glossary_term('Filibuster');
        public_glossary_term('<script>alert(1)</script>Quirk');
        $_GET['az'] = 'Q';
        $this->html = $this->render(fn () => (new GlossaryController())->index());
    });

    it('draws the page around the list', function () {
        expect($this->html)->toStartWith('[page_start]');
    });

    it('lists a term that starts with the letter', function () {
        expect($this->html)->toContain('>Quorum</a>');
    });

    it('leaves out a term that starts with another letter', function () {
        expect($this->html)->not->toContain('Filibuster</a></li>');
    });

    it('escapes a title', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('explains what the glossary is', function () {
        expect($this->html)->toContain('What is the glossary?');
    });

    it('invites a suggestion', function () {
        expect($this->html)->toContain('href="/addterm/"');
    });

    it('links the letters that have terms', function () {
        expect($this->html)->toContain('href="/glossary/?az=F"');
    });

    it('marks the letter being shown', function () {
        expect($this->html)->toContain('aria-current="page"');
    });

    it('titles the page by the letter', function () {
        expect($GLOBALS['DATA']->page_metadata('glossary', 'title'))->toBe('Q: Glossary index');
    });

    it('gives the page a viewport', function () {
        expect($GLOBALS['DATA']->page_metadata('glossary', 'responsive'))->toBeTrue();
    });
});

describe('GlossaryController::index() for a letter with no terms', function () {
    it('says so', function () {
        public_glossary_term('Quorum');
        $_GET['az'] = 'Z';

        $html = $this->render(fn () => (new GlossaryController())->index());

        expect($html)->toContain('No terms start with Z yet.');
    });
});

describe('GlossaryController::index() for a term', function () {
    beforeEach(function () {
        $this->first = public_glossary_term('Filibuster', 'A <strong>long</strong> speech. <script>alert(2)</script>', '<img src=x onerror=alert(3)>');
        $this->second = public_glossary_term('Quorum');
        $_GET['gl'] = (string) $this->second->glossary_id;
        $this->html = $this->render(fn () => (new GlossaryController())->index());
    });

    it('shows the title', function () {
        expect($this->html)->toContain('>Quorum</h1>');
    });

    it('links the term before it', function () {
        expect($this->html)->toContain('>&larr; Filibuster</a>');
    });

    it('wraps round to the first term after the last', function () {
        expect($this->html)->toContain('href="/glossary/?gl=' . $this->first->glossary_id . '" class="!text-teal-700 hover:!underline sm:text-right">Filibuster &rarr;');
    });

    it('links a Hansard search for the term', function () {
        expect($this->html)->toContain('href="/search/?s=%22Quorum%22"');
    });

    it('labels the page as a glossary term', function () {
        expect($this->html)->toContain('>Glossary Term</p>');
    });

    it('titles the page by the term', function () {
        expect($GLOBALS['DATA']->page_metadata('glossary', 'title'))->toBe('Quorum: Glossary item');
    });
});

describe('GlossaryController::index() for a term with unsafe text', function () {
    beforeEach(function () {
        $term = public_glossary_term('Filibuster', 'A <strong>long</strong> speech. <script>alert(2)</script>', '<img src=x onerror=alert(3)>');
        $_GET['gl'] = (string) $term->glossary_id;
        $this->html = $this->render(fn () => (new GlossaryController())->index());
    });

    it('keeps the few tags people may use', function () {
        expect($this->html)->toContain('<strong>long</strong>');
    });

    it('strips any other tag from the body', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('escapes the contributor\'s name', function () {
        expect($this->html)->toContain('&lt;img src=x onerror=alert(3)&gt; &lt;Example&gt;');
    });
});

describe('GlossaryController::index() for a body with a web address that holds a quote', function () {
    beforeEach(function () {
        $term = public_glossary_term('Filibuster', 'See http://example.test/a"onmouseover="alert(1)');
        $_GET['gl'] = (string) $term->glossary_id;
        $this->html = $this->render(fn () => (new GlossaryController())->index());
    });

    it('ends the link at the quote', function () {
        expect($this->html)->toContain('<a href="http://example.test/a">http://example.test/a</a>');
    });

    it('adds no attribute to the link', function () {
        expect($this->html)->not->toContain('<a href="http://example.test/a"onmouseover');
    });
});

/**
 * Approves the term a second time, as a re-approval or resubmission would.
 */
function public_glossary_approve_again(Glossary $term): void {
    $first = Editqueue::where('glossary_id', $term->glossary_id)->first();
    Editqueue::create(['user_id' => $first->user_id, 'edit_type' => 2, 'glossary_id' => $term->glossary_id, 'title' => $term->title, 'body' => $term->body, 'submitted' => '2026-11-02 10:00:00', 'approved' => true]);
}

describe('GlossaryController::index() for a term approved twice', function () {
    beforeEach(function () {
        $this->twice = public_glossary_term('Filibuster');
        public_glossary_approve_again($this->twice);
        public_glossary_term('Quorum');
    });

    it('lists the term once', function () {
        $_GET['az'] = 'F';

        $html = $this->render(fn () => (new GlossaryController())->index());

        expect(substr_count($html, '>Filibuster</a>'))->toBe(1);
    });

    it('links the term after it, not itself', function () {
        $_GET['gl'] = (string) $this->twice->glossary_id;

        $html = $this->render(fn () => (new GlossaryController())->index());

        expect($html)->toContain('>Quorum &rarr;</a>');
    });
});

describe('GlossaryController::index() for a term that is not there', function () {
    it('shows the index instead', function () {
        public_glossary_term('Quorum');
        $_GET['gl'] = '999999999';

        $html = $this->render(fn () => (new GlossaryController())->index());

        expect($html)->toContain('>Glossary index</h1>');
    });
});
