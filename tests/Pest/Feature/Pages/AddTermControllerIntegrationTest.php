<?php

/**
 * @file
 * The glossary page for suggesting a term, from the request to the HTML. Uses fictional people and terms.
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
use OpenAustralia\TWFY\Pages\AddTermController;
use OpenAustralia\TWFY\UserRoles;

uses(AdminControllerTestCase::class);

/**
 * The page's controller, with Hansard standing in for the search engine.
 */
function addterm_controller(int $count = 3, string $excerpts = '<p>An excerpt from Hansard.</p>'): AddTermController {
    return new class($count, $excerpts) extends AddTermController {

        public function __construct(private int $count, private string $excerpts) {
        }

        protected function occurrences(string $phrase): int {
            return $this->count;
        }

        protected function excerpts(string $phrase): string {
            return $this->excerpts;
        }

    };
}

/**
 * Stands in for a visitor who is signed out, or signed in without the right to add terms.
 */
function addterm_visitor(bool $signed_in, bool $allowed, int $user_id = 0): void {
    $GLOBALS['THEUSER'] = new class($signed_in, $allowed, $user_id) {

        public function __construct(private bool $signed_in, private bool $allowed, private int $user_id) {
        }

        public function isloggedin(): bool {
            return $this->signed_in;
        }

        public function is_able_to(string $action): bool {
            return $this->allowed;
        }

        public function user_id(): int {
            return $this->user_id;
        }

        public function password(): string {
            return 'stored-password-hash';
        }

    };
}

/**
 * The token the form carries for the signed-in test user.
 */
function addterm_token(): string {
    return UserRoles::token(test()->user->user_id, 'stored-password-hash', 'glossary-addterm');
}

beforeEach(function () {
    $GLOBALS['DATA'] = new DATA();
    // lib_filter.php makes this at the top of the file, which is not the global scope when a spec loads it.
    $GLOBALS['filter'] = new lib_filter();
    $this->user = User::create(['firstname' => 'Ada', 'lastname' => 'Example', 'email' => 'ada' . random_int(1000, 9999) . '@example.test']);
    addterm_visitor(true, true, $this->user->user_id);
});

describe('AddTermController::index() with no phrase', function () {
    beforeEach(function () {
        $this->html = $this->render(fn () => addterm_controller()->index());
    });

    it('draws the page', function () {
        expect($this->html)->toStartWith('[page_start]');
    });

    it('explains how it works', function () {
        expect($this->html)->toContain('How this works');
    });

    it('asks for a phrase to search for', function () {
        expect($this->html)->toContain('Step 1: Search for a phrase');
    });

    it('gives examples that link to the glossary', function () {
        expect($this->html)->toContain('href="/glossary/?gl=2"');
    });

    it('offers the A to Z strip', function () {
        expect($this->html)->toContain('aria-label="Glossary letters"');
    });

    it('gives the page a viewport', function () {
        expect($GLOBALS['DATA']->page_metadata('glossary_addterm', 'responsive'))->toBeTrue();
    });

    it('titles the page', function () {
        expect($GLOBALS['DATA']->page_metadata('glossary_addterm', 'title'))->toBe('Add a glossary item');
    });
});

describe('AddTermController::index() for a phrase sent as a list', function () {
    it('asks for a phrase as if none was sent', function () {
        $_GET['g'] = ['point', 'of order'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('Step 1: Search for a phrase');
    });
});

describe('AddTermController::index() for a phrase that is in Hansard', function () {
    beforeEach(function () {
        $_GET['g'] = 'point of order';
        $this->html = $this->render(fn () => addterm_controller(3)->index());
    });

    it('says how often it appears', function () {
        expect($this->html)->toContain('<strong>3</strong> occurrences of');
    });

    it('offers a form to define it', function () {
        expect($this->html)->toContain('Add a definition for &ldquo;point of order&rdquo;');
    });

    it('keeps the phrase in the form', function () {
        expect($this->html)->toContain('<input type="hidden" name="g" value="point of order">');
    });

    it('shows the excerpts', function () {
        expect($this->html)->toContain('<p>An excerpt from Hansard.</p>');
    });

    it('posts the form to this page', function () {
        expect($this->html)->toContain('<form action="/addterm/" method="post"');
    });
});

describe('AddTermController::index() for a phrase with a definition already', function () {
    beforeEach(function () {
        $term = Glossary::create(['title' => 'quorum call', 'body' => 'A claim.', 'type' => 2, 'visible' => true]);
        foreach (['2026-11-01 10:00:00', '2026-11-02 10:00:00'] as $submitted) {
            Editqueue::create(['user_id' => test()->user->user_id, 'edit_type' => 2, 'glossary_id' => $term->glossary_id, 'title' => 'quorum call', 'body' => 'A claim.', 'submitted' => $submitted, 'approved' => true]);
        }
        $_GET['g'] = 'quorum';
        $this->html = $this->render(fn () => addterm_controller(2)->index());
    });

    it('lists the existing definition', function () {
        expect($this->html)->toContain('>quorum call</a>');
    });

    it('lists it once, however many times it was approved', function () {
        expect(substr_count($this->html, '>quorum call</a>'))->toBe(1);
    });
});

describe('AddTermController::index() for a phrase that is not in Hansard', function () {
    beforeEach(function () {
        $_GET['g'] = 'zzzzqx';
        $this->html = $this->render(fn () => addterm_controller(0)->index());
    });

    it('says so', function () {
        expect($this->html)->toContain('doesn&#039;t seem to appear in Hansard at all');
    });

    it('offers no form to define it', function () {
        expect($this->html)->not->toContain('name="definition"');
    });
});

describe('AddTermController::index() for a phrase that is too common', function () {
    it('says so', function () {
        $_GET['g'] = 'the';

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('appears too many times');
    });
});

describe('AddTermController::index() for a phrase with markup in it', function () {
    beforeEach(function () {
        $_GET['g'] = '"><script>alert(1)</script> quorum';
        $this->html = $this->render(fn () => addterm_controller(0)->index());
    });

    it('draws no script tag', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('strips the tags from the phrase in the message', function () {
        expect($this->html)->toContain('&quot;alert(1) quorum&quot;');
    });
});

describe('AddTermController::index() for a visitor who is signed out', function () {
    beforeEach(function () {
        addterm_visitor(false, false);
        $_GET['g'] = 'point of order';
        $this->html = $this->render(fn () => addterm_controller()->index());
    });

    it('sends the search to the log in prompt', function () {
        expect($this->html)->toContain('<form action="/user/prompt/" method="get"');
    });

    it('offers no form to define the phrase', function () {
        expect($this->html)->not->toContain('name="definition"');
    });
});

describe('AddTermController::index() for a preview', function () {
    it('shows the entry', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'previewterm' => 'Preview', 'definition' => 'A <strong>claim</strong> that a rule is broken. <script>alert(1)</script>'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('<strong>claim</strong>');
    });

    it('strips any other tag from the definition', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'previewterm' => 'Preview', 'definition' => 'A claim. <script>alert(1)</script>'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->not->toContain('<script>');
    });

    it('ends a web address at a quote', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'previewterm' => 'Preview', 'definition' => 'See http://example.test/a"onmouseover="alert(1)'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('<a href="http://example.test/a">http://example.test/a</a>');
    });

    it('keeps the definition in the form', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'previewterm' => 'Preview', 'definition' => 'A claim.'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('>A claim.</textarea>');
    });

    it('asks for a definition when there is none', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'previewterm' => 'Preview', 'definition' => ''];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('Please write a definition to preview.');
    });
});

describe('AddTermController::index() for a submission', function () {
    beforeEach(function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'submitterm' => 'Post', 'definition' => 'A <strong>claim</strong> that a rule is broken.'];
    });

    it('queues the term for a moderator', function () {
        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->pluck('title')->all())->toBe(['point of order']);
    });

    it('copies the name onto the submission', function () {
        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->value('contributor_name'))->toBe('Ada Example');
    });

    it('thanks the visitor', function () {
        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('Thank you for your help');
    });

    it('leaves the term waiting, not approved', function () {
        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->first()->approved)->toBeNull();
    });

    it('refuses a second suggestion straight away', function () {
        $this->render(fn () => addterm_controller()->index());
        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('one term every 20 seconds');
    });

    it('queues only the first', function () {
        $this->render(fn () => addterm_controller()->index());
        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->count())->toBe(1);
    });

    it('queues nothing for a visitor who is signed out', function () {
        addterm_visitor(false, false);

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('title', 'point of order')->count())->toBe(0);
    });

    it('asks a visitor who is signed out to sign in', function () {
        addterm_visitor(false, false);

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('you need to be signed in');
    });

    it('asks for a definition when there is none', function () {
        $_POST['definition'] = '';

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('You haven&#039;t entered a definition.');
    });
});

describe('AddTermController::index() for a visitor who is signed in without the right to add terms', function () {
    beforeEach(function () {
        addterm_visitor(true, false, test()->user->user_id);
    });

    it('says the account cannot add terms when searching', function () {
        $_GET['g'] = 'point of order';

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('your account can&#039;t add glossary terms');
    });

    it('offers no form when searching', function () {
        $_GET['g'] = 'point of order';

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->not->toContain('name="definition"');
    });

    it('says the account cannot add terms when posting', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('your account can&#039;t add glossary terms');
    });

    it('queues nothing when posting', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('title', 'point of order')->count())->toBe(0);
    });
});

describe('AddTermController::index() for a phrase typed in quotation marks', function () {
    it('searches for the words without the quotation marks', function () {
        $_GET['g'] = '"point of order"';

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('<input type="hidden" name="g" value="point of order">');
    });

    it('queues the phrase without the quotation marks', function () {
        $_POST = ['token' => addterm_token(), 'g' => '“point of order”', 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->pluck('title')->all())->toBe(['point of order']);
    });
});

describe('AddTermController::index() for a phrase with formatting tags in it', function () {
    it('shows the phrase as plain text in the preview', function () {
        $_POST = ['token' => addterm_token(), 'g' => '<em>point</em> of order', 'previewterm' => 'Preview', 'definition' => 'A claim.'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('>point of order</h3>');
    });

    it('queues the phrase as plain text', function () {
        $_POST = ['token' => addterm_token(), 'g' => '<em>point</em> of order', 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->pluck('title')->all())->toBe(['point of order']);
    });
});

describe('AddTermController::index() for a post without the right token', function () {
    it('queues nothing when the token is missing', function () {
        $_POST = ['g' => 'point of order', 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->count())->toBe(0);
    });

    it('queues nothing when the token is wrong', function () {
        $_POST = ['token' => 'not-the-token', 'g' => 'point of order', 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->count())->toBe(0);
    });

    it('says the form has expired', function () {
        $_POST = ['g' => 'point of order', 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('that form has expired');
    });

    it('keeps the definition so it can be posted again', function () {
        $_POST = ['g' => 'point of order', 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('>A claim.</textarea>');
    });

    it('shows no preview when the token is missing', function () {
        $_POST = ['g' => 'point of order', 'previewterm' => 'Preview', 'definition' => 'A claim.'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->not->toContain('>point of order</h3>');
    });
});

describe('AddTermController::index() for the form', function () {
    it('carries the token', function () {
        $_GET['g'] = 'point of order';

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('<input type="hidden" name="token" value="' . addterm_token() . '">');
    });
});

describe('AddTermController::index() for a phrase or definition that is too long', function () {
    it('refuses a phrase over 255 characters', function () {
        $_POST = ['token' => addterm_token(), 'g' => str_repeat('a', 256), 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('that phrase is too long');
    });

    it('queues nothing for a phrase that is too long', function () {
        $_POST = ['token' => addterm_token(), 'g' => str_repeat('a', 256), 'submitterm' => 'Post', 'definition' => 'A claim.'];

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->count())->toBe(0);
    });

    it('refuses a definition over the column size in bytes', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'submitterm' => 'Post', 'definition' => str_repeat('é', 33000)];

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('that definition is too long');
    });

    it('queues nothing for a definition that is too long', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'submitterm' => 'Post', 'definition' => str_repeat('é', 33000)];

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->count())->toBe(0);
    });

    it('accepts a long definition that fits', function () {
        $_POST = ['token' => addterm_token(), 'g' => 'point of order', 'submitterm' => 'Post', 'definition' => str_repeat('é', 30000)];

        $this->render(fn () => addterm_controller()->index());

        expect(Editqueue::where('user_id', $this->user->user_id)->count())->toBe(1);
    });
});

describe('AddTermController::index() for the signed-out search form', function () {
    beforeEach(function () {
        addterm_visitor(false, false);
        $this->html = $this->render(fn () => addterm_controller()->index());
    });

    it('sends no return address, so the sign-in page can keep the phrase', function () {
        expect($this->html)->not->toContain('name="ret"');
    });

    it('tells the sign-in page it is for the glossary', function () {
        expect($this->html)->toContain('<input type="hidden" name="type" value="2">');
    });
});

describe('AddTermController::index() for the definition form', function () {
    it('describes the tags and addresses the filter keeps', function () {
        $_GET['g'] = 'point of order';

        $html = $this->render(fn () => addterm_controller()->index());

        expect($html)->toContain('Use &lt;strong&gt; for bold text and &lt;em&gt; for italics. Addresses starting with http:// or https:// become links automatically.');
    });
});
