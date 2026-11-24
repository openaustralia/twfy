<?php

namespace OpenAustralia\TWFY\Pages;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\Glossary;

/**
 * The public glossary: an A to Z index of the approved terms, and a page for each term.
 */
class GlossaryController extends PageController {

    /**
     * Shows one term when the address names one, else the terms that start with a letter.
     */
    public function index(): void {
        global $DATA;

        $terms = Glossary::approved()->get();
        $id = isset($_GET['gl']) && is_numeric($_GET['gl']) ? (int) $_GET['gl'] : null;
        $term = $id === null ? null : $terms->firstWhere('glossary_id', $id);
        $letter = self::letter($terms, $term);

        $DATA->set_page_metadata('glossary', 'title', $term === null ? $letter . ': Glossary index' : $term->title . ': Glossary item');

        $this->page('glossary', function () use ($terms, $term, $letter) {
            $letters = self::letters($terms, $letter);
            $addUrl = WEBPATH . 'addterm/';

            if ($term === null) {
                return $this->view('glossary.browse', [
                    'letters' => $letters,
                    'letter' => $letter,
                    'terms' => $terms->filter(fn (Glossary $each): bool => self::initial($each) === $letter)
                      ->map(fn (Glossary $each): array => ['title' => (string) $each->title, 'url' => self::termUrl($each)])
                      ->values()
                      ->all(),
                    'add_url' => $addUrl,
                ]);
            }

            $position = $terms->search(fn (Glossary $each): bool => $each->glossary_id === $term->glossary_id);
            $previous = $terms[($position - 1 + $terms->count()) % $terms->count()];
            $next = $terms[($position + 1) % $terms->count()];

            return $this->view('glossary.term', [
                'letters' => $letters,
                'term' => [
                    'title' => (string) $term->title,
                    'body_html' => self::linked((string) $term->body),
                    'contributor' => trim($term->firstname . ' ' . $term->lastname),
                    'contributor_url' => WEBPATH . 'user/?u=' . $term->user_id,
                    'search_url' => WEBPATH . 'search/?' . http_build_query(['s' => '"' . $term->title . '"']),
                ],
                'previous' => ['title' => (string) $previous->title, 'url' => self::termUrl($previous)],
                'next' => ['title' => (string) $next->title, 'url' => self::termUrl($next)],
                'add_url' => $addUrl,
            ]);
        });
    }

    /**
     * The letter to show: the one asked for, else the term's first letter, else A.
     *
     * @param \Illuminate\Support\Collection<int, \OpenAustralia\TWFY\Models\Glossary> $terms
     *   The approved terms.
     * @param \OpenAustralia\TWFY\Models\Glossary|null $term
     *   The term being shown, if any.
     */
    private static function letter(Collection $terms, ?Glossary $term): string {
        $asked = strtoupper(substr((string) ($_GET['az'] ?? ''), 0, 1));
        if (preg_match('/^[A-Z]$/', $asked) === 1) {
            return $asked;
        }
        return $term === null ? 'A' : self::initial($term);
    }

    /**
     * The letters A to Z, each with its address when some term starts with it.
     *
     * @param \Illuminate\Support\Collection<int, \OpenAustralia\TWFY\Models\Glossary> $terms
     *   The approved terms.
     * @param string $current
     *   The letter being shown.
     *
     * @return list<array{letter: string, url: string|null, current: bool}>
     *   One entry for each letter.
     */
    private static function letters(Collection $terms, string $current): array {
        $used = $terms->map(fn (Glossary $term): string => self::initial($term))->unique()->all();
        return array_map(fn (string $letter): array => [
            'letter' => $letter,
            'url' => in_array($letter, $used, true) ? WEBPATH . 'glossary/?az=' . $letter : null,
            'current' => $letter === $current,
        ], range('A', 'Z'));
    }

    /**
     * The first letter of a term's title, in capitals.
     */
    private static function initial(Glossary $term): string {
        return strtoupper(substr((string) $term->title, 0, 1));
    }

    /**
     * The address of a term's page.
     */
    private static function termUrl(Glossary $term): string {
        return WEBPATH . 'glossary/?gl=' . $term->glossary_id;
    }

    /**
     * A term's body with the other terms in it linked, after stripping all but the few tags people may use.
     *
     * This is the only HTML a view prints without escaping it.
     */
    private static function linked(string $body): string {
        return (new \GLOSSARY(['sort' => 'regexp_replace']))->glossarise(filter_user_input($body, 'comment'), 0, 1);
    }

}
