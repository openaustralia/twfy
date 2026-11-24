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

        $terms = Glossary::approvedTerms();
        $id = isset($_GET['gl']) && is_numeric($_GET['gl']) ? (int) $_GET['gl'] : null;
        $term = $id === null ? null : $terms->firstWhere('glossary_id', $id);
        $letter = self::letter($terms, $term);

        $DATA->set_page_metadata('glossary', 'title', $term === null ? $letter . ': Glossary index' : $term->title . ': Glossary item');

        $this->page('glossary', function () use ($terms, $term, $letter) {
            $letters = GlossaryLetters::for($terms, $letter);
            $addUrl = GlossaryUrls::add();

            if ($term === null) {
                return $this->view('glossary.browse', [
                    'letters' => $letters,
                    'letter' => $letter,
                    'terms' => $terms->filter(fn (Glossary $each): bool => GlossaryLetters::initial($each) === $letter)
                      ->map(fn (Glossary $each): array => ['title' => (string) $each->title, 'url' => GlossaryUrls::term($each->glossary_id)])
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
                    'body_html' => GlossaryBody::html((string) $term->body, (int) $term->glossary_id),
                    'contributor' => trim($term->firstname . ' ' . $term->lastname),
                    'contributor_url' => WEBPATH . 'user/?u=' . $term->user_id,
                    'search_url' => GlossaryUrls::search((string) $term->title),
                ],
                'previous' => ['title' => (string) $previous->title, 'url' => GlossaryUrls::term($previous->glossary_id)],
                'next' => ['title' => (string) $next->title, 'url' => GlossaryUrls::term($next->glossary_id)],
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
        if (preg_match('/^[A-Z#]$/', $asked) === 1) {
            return $asked;
        }
        return $term === null ? 'A' : GlossaryLetters::initial($term);
    }

}
