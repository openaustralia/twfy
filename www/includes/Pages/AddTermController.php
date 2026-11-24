<?php

namespace OpenAustralia\TWFY\Pages;

use OpenAustralia\TWFY\Models\Glossary;
use OpenAustralia\TWFY\UserRoles;

/**
 * The page for suggesting a glossary term: search for a phrase, check it appears in Hansard, then write its definition.
 */
class AddTermController extends PageController {

    /**
     * The phrase to search for, the definition to preview or the suggestion to queue, depending on the request.
     */
    public function index(): void {
        global $THEUSER, $DATA;

        $phrase = self::cleanPhrase(self::input('g'));
        $definition = self::input('definition');
        $submitting = self::input('submitterm') !== '';
        $previewing = !$submitting && self::input('previewterm') !== '';
        $searching = $phrase !== '' && !$submitting && !$previewing;
        $signedIn = (bool) $THEUSER->isloggedin();
        $canAdd = $signedIn && $THEUSER->is_able_to('addterm');
        $denied = $signedIn ? 'Sorry, your account can\'t add glossary terms.' : 'Sorry, you need to be signed in to add a glossary term.';

        $DATA->set_page_metadata('glossary_addterm', 'title', 'Add a glossary item');

        $notice = null;
        $thanks = null;
        $matches = [];
        $count = 0;
        $form = null;
        $preview = null;
        $excerpts = '';

        if (!$canAdd && ($submitting || $previewing || $searching)) {
            $notice = $denied;
            $matches = $phrase === '' ? [] : self::matches($phrase);
        } elseif (($submitting || $previewing) && !self::tokenIsValid()) {
            $notice = 'Sorry, that form has expired. Please check your definition and post it again.';
            $form = ['phrase' => $phrase, 'body' => $definition];
        } elseif ($submitting) {
            $result = TermSubmission::submit((int) $THEUSER->user_id(), $phrase, $definition);
            if ($result['ok']) {
                $thanks = $result['title'];
            } else {
                $notice = $result['message'];
                $form = ['phrase' => $phrase, 'body' => $definition];
            }
        } elseif ($previewing) {
            if (trim($definition) === '') {
                $notice = 'Please write a definition to preview.';
            } else {
                $preview = ['title' => $phrase, 'body_html' => self::linked($definition)];
            }
            $form = ['phrase' => $phrase, 'body' => $definition];
        } elseif ($searching) {
            if (in_array(strtolower($phrase), (new \GLOSSARY())->get_stopwords(), true)) {
                $notice = 'Sorry, that phrase appears too many times to be useful as a link within the parliamentary record.';
                $phrase = '';
            } else {
                $count = $this->occurrences($phrase);
                if ($count === 0) {
                    $notice = 'Unfortunately "' . $phrase . '" doesn\'t seem to appear in Hansard at all.';
                    $phrase = '';
                } else {
                    $matches = self::matches($phrase);
                    $form = ['phrase' => $phrase, 'body' => ''];
                    $excerpts = $this->excerpts($phrase);
                }
            }
        }

        $this->page('glossary_addterm', fn () => $this->view('glossary.addterm', [
            'signed_in' => $signedIn,
            'can_add' => $canAdd,
            'token' => self::token(),
            'search_url' => $signedIn ? WEBPATH . 'addterm/' : WEBPATH . 'user/prompt/',
            'return_url' => WEBPATH . 'addterm/',
            'phrase' => $phrase,
            'notice' => $notice,
            'thanks' => $thanks,
            'matches' => $matches,
            'count' => $count,
            'form' => $form,
            'preview' => $preview,
            'excerpts_html' => $excerpts,
            'letters' => GlossaryLetters::for(Glossary::approvedTerms()),
            'examples' => [['title' => 'Standing Order 94A', 'url' => WEBPATH . 'glossary/?gl=2'], ['title' => 'interjecting', 'url' => WEBPATH . 'glossary/?gl=1']],
            'browse_url' => WEBPATH . 'glossary/',
        ]));
    }

    /**
     * How many times a phrase appears in Hansard.
     */
    protected function occurrences(string $phrase): int {
        return (int) $this->searchEngine($phrase)->run_count();
    }

    /**
     * The Hansard search for an exact phrase, which HANSARDLIST reads from the global.
     */
    private function searchEngine(string $phrase): \SEARCHENGINE {
        global $SEARCHENGINE;

        $SEARCHENGINE = new \SEARCHENGINE('"' . $phrase . '"');
        return $SEARCHENGINE;
    }

    /**
     * A few excerpts from Hansard that use the phrase, as the search results template draws them.
     *
     * The template's HTML is the only thing a view prints without escaping, along with a definition's body.
     */
    protected function excerpts(string $phrase): string {
        $this->searchEngine($phrase);
        ob_start();
        (new \HANSARDLIST())->display('search', ['s' => $phrase, 'num' => 5, 'view_override' => 'glossary_search']);
        return (string) ob_get_clean();
    }

    /**
     * A definition with the other terms in it linked, after stripping all but the few tags people may use.
     */
    private static function linked(string $body): string {
        return (new \GLOSSARY(['sort' => 'regexp_replace']))->glossarise(filter_user_input($body, 'comment'), 0, 1);
    }

    /**
     * The token the form carries, so another site can't post it for a signed-in user.
     *
     * It is empty unless someone is signed in, as a token with no password hash to key it could be made by anyone.
     */
    private static function token(): string {
        global $THEUSER;

        if (!$THEUSER->isloggedin() || (string) $THEUSER->password() === '') {
            return '';
        }
        return UserRoles::token((int) $THEUSER->user_id(), (string) $THEUSER->password(), 'glossary-addterm');
    }

    /**
     * Whether the request posted the signed-in user's token. Only $_POST is read, so it never appears in a URL.
     */
    private static function tokenIsValid(): bool {
        $token = self::token();
        $posted = $_POST['token'] ?? '';
        return $token !== '' && is_string($posted) && hash_equals($token, $posted);
    }

    /**
     * The terms we already have that contain a phrase.
     *
     * @return list<array{title: string, url: string}>
     *   Each term's title and address.
     */
    private static function matches(string $phrase): array {
        return Glossary::approvedTerms($phrase)
          ->map(fn (Glossary $term): array => ['title' => (string) $term->title, 'url' => WEBPATH . 'glossary/?gl=' . $term->glossary_id])
          ->all();
    }

    /**
     * A phrase as plain text, without tags or quotation marks, which the search adds itself.
     */
    private static function cleanPhrase(string $text): string {
        return trim(str_replace(['"', '“', '”'], '', filter_user_input($text, 'strict')));
    }

    /**
     * A request value from the form or the query string, as text.
     */
    private static function input(string $name): string {
        return (string) ($_POST[$name] ?? $_GET[$name] ?? '');
    }

}
