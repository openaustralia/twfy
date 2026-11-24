<?php

/**
 * @file
 * The links between glossary terms on a term page. Uses fictional people and terms.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/CommittedAdminControllerTestCase.php';
require_once __DIR__ . '/../../Support/UrlStandIn.php';
require_once __DIR__ . '/../../../../www/includes/data.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../../../www/includes/lib_filter.php';
require_once __DIR__ . '/../../../../www/includes/easyparliament/glossary.php';

use OpenAustralia\TWFY\Models\Editqueue;
use OpenAustralia\TWFY\Models\Glossary;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Pages\GlossaryController;

uses(CommittedAdminControllerTestCase::class);

beforeEach(function () {
    $GLOBALS['DATA'] = new DATA();
    // lib_filter.php makes this at the top of the file, which is not the global scope when a spec loads it.
    $GLOBALS['filter'] = new lib_filter();
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
 * Adds an approved term.
 */
function linking_term(string $title, string $body): Glossary {
    $user = test()->user;
    $term = Glossary::create(['title' => $title, 'body' => $body, 'type' => 2, 'visible' => true]);
    Editqueue::create(['user_id' => $user->user_id, 'edit_type' => 2, 'glossary_id' => $term->glossary_id, 'title' => $title, 'body' => $body, 'submitted' => '2026-11-01 10:00:00', 'approved' => true]);
    test()->terms->append($term);
    return $term;
}

describe('GlossaryController::index() for a body that names terms', function () {
    beforeEach(function () {
        linking_term('Quorum', 'A minimum number of members.');
        $term = linking_term('Filibuster', 'A Filibuster is a long speech, often used to block a Quorum.');
        $_GET['gl'] = (string) $term->glossary_id;
        $this->html = $this->render(fn () => (new GlossaryController())->index());
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
