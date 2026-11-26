<?php

/**
 * @file
 * The admin glossary pages, from the database to the HTML. Uses fictional people and terms.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../../../www/includes/lib_filter.php';
require_once __DIR__ . '/../../../../www/includes/easyparliament/glossary.php';

use OpenAustralia\TWFY\Admin\GlossaryController;
use OpenAustralia\TWFY\Models\Editqueue;
use OpenAustralia\TWFY\Models\Glossary;
use OpenAustralia\TWFY\Models\User;

// The legacy glossary builds each term's link with the URL class, which other specs also stand in for.
if (!class_exists('URL')) {
    /**
     * Minimal URL stand-in.
     */
    class URL {

        public function __construct(private string $page) {
        }

        /**
         * @param array<string, string> $params
         *   Query values.
         */
        public function insert(array $params): void {
        }

        public function generate(string $type = ''): string {
            return '/' . $this->page . '/';
        }

    }
}

uses(AdminControllerTestCase::class);

beforeEach(function () {
    // lib_filter.php makes this at the top of the file, which is not the global scope when a spec loads it.
    $GLOBALS['filter'] = new lib_filter();
});

/**
 * A submission by a fictional person, approved or waiting.
 *
 * @param bool|null $approved
 *   true for an approved term, null for one waiting for a moderator.
 */
function glossary_page_submission(string $title, string $body, ?bool $approved): Editqueue {
    $user = User::create(['firstname' => 'Ada', 'lastname' => '<Example>', 'email' => 'ada' . random_int(1000, 9999) . '@example.test']);
    $term = $approved ? Glossary::create(['title' => $title, 'body' => $body, 'contributor_name' => $user->fullName(), 'type' => 2, 'visible' => true]) : null;
    return Editqueue::create(['user_id' => $user->user_id, 'contributor_name' => $user->fullName(), 'edit_type' => 2, 'glossary_id' => $term?->glossary_id, 'title' => $title, 'body' => $body, 'submitted' => '2026-11-01 10:00:00', 'approved' => $approved]);
}

describe('GlossaryController::index()', function () {
    beforeEach(function () {
        $this->signInAs('Superuser', 1);
        $this->submission = glossary_page_submission('Quorum', '<strong>Enough</strong> members <script>alert(1)</script>', true);
        $this->html = $this->render(fn () => (new GlossaryController())->index());
    });

    it('draws the page around the terms', function () {
        expect($this->html)->toStartWith('[page_start]');
    });

    it('lists an approved term', function () {
        expect($this->html)->toContain('>Quorum</h3>');
    });

    it('keeps the few tags people may use', function () {
        expect($this->html)->toContain('<strong>Enough</strong>');
    });

    it('strips any other tag from the body', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('escapes who contributed it', function () {
        expect($this->html)->toContain('Ada &lt;Example&gt;');
    });

    it('links to delete it', function () {
        expect($this->html)->toContain('href="/admin/glossary.php?delete_confirm=' . $this->submission->glossary_id . '"');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_glossary');
    });
});

describe('GlossaryController::index() with a term to delete', function () {
    it('deletes it', function () {
        $this->signInAs('Superuser', 1);
        $submission = glossary_page_submission('Quorum', 'Enough members', true);
        $_GET['delete_confirm'] = (string) $submission->glossary_id;

        $this->render(fn () => (new GlossaryController())->index());

        expect(Glossary::find($submission->glossary_id))->toBeNull();
    });

    it('waits until the admin check has passed', function () {
        $this->signInAs('Superuser', 1);
        $submission = glossary_page_submission('Quorum', 'Enough members', true);
        $_GET['delete_confirm'] = (string) $submission->glossary_id;
        $GLOBALS['PAGE'] = new class($submission->glossary_id) {

            public bool $still_there = false;

            public function __construct(private int $id) {
            }

            public function __call(string $name, array $args): string {
                if ($name === 'page_start') {
                    $this->still_there = Glossary::find($this->id) !== null;
                }
                return '';
            }

        };

        $this->render(fn () => (new GlossaryController())->index());

        expect($GLOBALS['PAGE']->still_there)->toBeTrue();
    });
});

describe('GlossaryController::pending()', function () {
    beforeEach(function () {
        $this->signInAs('Superuser', 1);
        $this->submission = glossary_page_submission('Filibuster <b>', 'A long <script>alert(1)</script> speech', null);
    });

    describe('with a submission waiting', function () {
        beforeEach(function () {
            $this->html = $this->render(fn () => (new GlossaryController())->pending());
        });

        it('lists it with its title escaped', function () {
            expect($this->html)->toContain('Filibuster &lt;b&gt;');
        });

        it('draws no script tag', function () {
            expect($this->html)->not->toContain('<script>');
        });

        it('offers a checkbox to approve it', function () {
            expect($this->html)->toContain('name="approve[]" value="' . $this->submission->edit_id . '"');
        });

        it('links to approve it', function () {
            expect($this->html)->toContain('href="/admin/glossary_pending.php?approve=' . $this->submission->edit_id . '"');
        });

        it('sets the page key for the menu', function () {
            expect($GLOBALS['this_page'])->toBe('admin_glossary_pending');
        });
    });

    describe('with nothing waiting', function () {
        it('says so', function () {
            Editqueue::query()->delete();

            $html = $this->render(fn () => (new GlossaryController())->pending());

            expect($html)->toContain('Nothing pending');
        });
    });

    describe('to approve one', function () {
        beforeEach(function () {
            $_GET['approve'] = (string) $this->submission->edit_id;
            $this->render(fn () => (new GlossaryController())->pending());
        });

        it('makes it a visible term', function () {
            expect(Glossary::approved()->pluck('glossary.title')->all())->toContain('Filibuster <b>');
        });

        it('records who approved it', function () {
            expect(Editqueue::find($this->submission->edit_id)->editor_id)->toBe(1);
        });

        it('gives the term the name copied at submission, even if the account is renamed first', function () {
            $term = Glossary::approved()->where('glossary.title', 'Filibuster <b>')->first();

            expect($term->contributor_name)->toBe('Ada <Example>');
        });
    });

    describe('to approve several from the form', function () {
        it('approves each', function () {
            $second = glossary_page_submission('Gag', 'Ends debate', null);
            $_POST['approve'] = [(string) $this->submission->edit_id, (string) $second->edit_id];

            $this->render(fn () => (new GlossaryController())->pending());

            expect(Glossary::approved()->count())->toBe(2);
        });
    });

    describe('to decline one', function () {
        it('turns it down without making a term', function () {
            $_GET['decline'] = (string) $this->submission->edit_id;

            $this->render(fn () => (new GlossaryController())->pending());

            expect([Editqueue::find($this->submission->edit_id)->approved, Glossary::count()])->toBe([false, 0]);
        });
    });

    describe('to modify one', function () {
        beforeEach(function () {
            $_GET['modify'] = (string) $this->submission->edit_id;
            $this->html = $this->render(fn () => (new GlossaryController())->pending());
        });

        it('shows the title in a form', function () {
            expect($this->html)->toContain('name="g" value="Filibuster &lt;b&gt;"');
        });

        it('guesses the Wikipedia link, encoded', function () {
            expect($this->html)->toContain('https://en.wikipedia.org/wiki/Filibuster_%3Cb%3E');
        });

        it('previews the body without script', function () {
            expect($this->html)->not->toContain('<script>');
        });
    });

    describe('to preview an edit', function () {
        it('shows the posted text', function () {
            $_POST = ['modify' => (string) $this->submission->edit_id, 'previewterm' => 'Preview', 'g' => 'Gag', 'definition' => 'Ends debate', 'wikiguess' => 'on'];

            $html = $this->render(fn () => (new GlossaryController())->pending());

            expect($html)->toContain('name="g" value="Gag"');
        });

        it('remembers the Wikipedia tick', function () {
            $_POST = ['modify' => (string) $this->submission->edit_id, 'previewterm' => 'Preview', 'g' => 'Gag', 'definition' => 'Ends debate', 'wikiguess' => 'on'];

            $html = $this->render(fn () => (new GlossaryController())->pending());

            expect($html)->toContain('name="wikiguess" checked');
        });
    });

    describe('to modify one that is not waiting', function () {
        it('shows the list', function () {
            $_GET['modify'] = '999999999';

            $html = $this->render(fn () => (new GlossaryController())->pending());

            expect($html)->toContain('Approve checked items');
        });
    });
});
