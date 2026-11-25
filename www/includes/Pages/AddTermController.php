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

        $DATA->set_page_metadata('glossary_addterm', 'title', 'Add a glossary item');

        $phrase = self::cleanPhrase($this->input('g'));
        $signedIn = (bool) $THEUSER->isloggedin();
        $step = $this->step($phrase, $this->input('definition'), $signedIn, $signedIn && $THEUSER->is_able_to('addterm'));

        $this->page('glossary_addterm', fn () => $this->view('glossary.addterm', $step + [
            'signed_in' => $signedIn,
            'token' => self::token(),
            'search_url' => $signedIn ? GlossaryUrls::add() : GlossaryUrls::signIn(),
            'letters' => GlossaryLetters::for(Glossary::approvedTerms()),
            'examples' => [['title' => 'Standing Order 94A', 'url' => GlossaryUrls::term(2)], ['title' => 'interjecting', 'url' => GlossaryUrls::term(1)]],
        ]));
    }

    /**
     * What the page shows for this request, whichever step it is.
     *
     * @return array<string, mixed>
     *   The notice, form, matches and so on the view draws.
     */
    private function step(string $phrase, string $definition, bool $signedIn, bool $canAdd): array {
        $submitting = $this->input('submitterm') !== '';
        $previewing = !$submitting && $this->input('previewterm') !== '';
        $posting = $submitting || $previewing;

        if (!$canAdd && ($posting || $phrase !== '')) {
            $shown = $this->denied($phrase, $signedIn);
        } elseif ($posting && !$this->tokenIsValid()) {
            $shown = ['notice' => 'Sorry, that form has expired. Please check your definition and post it again.', 'form' => self::form($phrase, $definition)];
        } elseif ($submitting) {
            $shown = self::submit($phrase, $definition);
        } elseif ($previewing) {
            $shown = self::preview($phrase, $definition);
        } elseif ($phrase !== '') {
            $shown = $this->search($phrase);
        } else {
            $shown = [];
        }

        return $shown + [
            'phrase' => $phrase,
            'notice' => null,
            'thanks' => null,
            'matches' => [],
            'count' => 0,
            'form' => null,
            'preview' => null,
            'excerpts_html' => '',
        ];
    }

    /**
     * Turns away a visitor who can't add terms, but still lists the terms we have for a phrase.
     *
     * @return array<string, mixed>
     *   What the view draws.
     */
    private function denied(string $phrase, bool $signedIn): array {
        return [
            'notice' => $signedIn ? 'Sorry, your account can\'t add glossary terms.' : 'Sorry, you need to be signed in to add a glossary term.',
            'matches' => $phrase === '' ? [] : self::matches($phrase),
        ];
    }

    /**
     * Queues a suggested term.
     *
     * @return array<string, mixed>
     *   A thank you, or the reason it was not queued with the form to try again.
     */
    private static function submit(string $phrase, string $definition): array {
        global $THEUSER;

        $result = TermSubmission::submit((int) $THEUSER->user_id(), $phrase, $definition);
        if ($result['ok']) {
            return ['thanks' => $result['title']];
        }
        return ['notice' => $result['message'], 'form' => self::form($phrase, $definition)];
    }

    /**
     * Shows how a definition will look, beside the form to change or post it.
     *
     * @return array<string, mixed>
     *   The preview, or a reminder to write a definition, with the form.
     */
    private static function preview(string $phrase, string $definition): array {
        $shown = ['form' => self::form($phrase, $definition)];
        if (trim($definition) === '') {
            return $shown + ['notice' => 'Please write a definition to preview.'];
        }
        return $shown + ['preview' => ['title' => $phrase, 'body_html' => GlossaryBody::html($definition)]];
    }

    /**
     * Checks a phrase is worth defining, then offers the form with Hansard excerpts to write from.
     *
     * @return array<string, mixed>
     *   The matches, count, form and excerpts, or why the phrase can't be defined.
     */
    private function search(string $phrase): array {
        if (in_array(strtolower($phrase), (new \GLOSSARY())->get_stopwords(), true)) {
            return ['phrase' => '', 'notice' => 'Sorry, that phrase appears too many times to be useful as a link within the parliamentary record.'];
        }

        $count = $this->occurrences($phrase);
        if ($count === 0) {
            return ['phrase' => '', 'notice' => 'Unfortunately "' . $phrase . '" doesn\'t seem to appear in Hansard at all.'];
        }

        return [
            'count' => $count,
            'matches' => self::matches($phrase),
            'form' => self::form($phrase),
            'excerpts_html' => $this->excerpts($phrase),
        ];
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

        return $SEARCHENGINE = new \SEARCHENGINE('"' . $phrase . '"');
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
    private function tokenIsValid(): bool {
        $token = self::token();
        $posted = $this->request()->post('token');
        return $token !== '' && is_string($posted) && hash_equals($token, $posted);
    }

    /**
     * What the definition form shows: the phrase being defined, and the definition so far.
     *
     * @return array{phrase: string, body: string}
     *   The phrase and the definition.
     */
    private static function form(string $phrase, string $body = ''): array {
        return ['phrase' => $phrase, 'body' => $body];
    }

    /**
     * The terms we already have that contain a phrase.
     *
     * @return list<array{title: string, url: string}>
     *   Each term's title and address.
     */
    private static function matches(string $phrase): array {
        return Glossary::approvedTerms($phrase)
          ->map(fn (Glossary $term): array => ['title' => (string) $term->title, 'url' => GlossaryUrls::term($term->glossary_id)])
          ->all();
    }

    /**
     * A phrase as plain text, without tags or quotation marks, which the search adds itself.
     */
    private static function cleanPhrase(string $text): string {
        return trim(str_replace(['"', '“', '”'], '', filter_user_input($text, 'strict')));
    }

}
