<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\Models\Editqueue;
use OpenAustralia\TWFY\Models\Glossary;

/**
 * The admin pages for glossary terms: the approved ones, and submissions waiting for a moderator.
 */
class GlossaryController extends AdminController {

    /**
     * The approved terms, each with a link to delete it.
     */
    public function index(): void {
        $this->page('admin_glossary', function () {
            global $THEUSER;

            $this->decide((int) $THEUSER->user_id());
            $delete_id = (int) self::input('delete_confirm');
            if ($delete_id > 0) {
                Glossary::whereKey($delete_id)->delete();
            }

            $glossary = self::glossary();
            $terms = Glossary::approved()->get()->map(fn (Glossary $term): array => [
                'id' => $term->glossary_id,
                'title' => $term->title,
                'body_html' => self::linked($glossary, (string) $term->body),
                'user' => ['name' => trim($term->firstname . ' ' . $term->lastname), 'url' => $this->userUrl($term->user_id)],
                'delete_url' => $this->adminUrl('glossary.php', ['delete_confirm' => $term->glossary_id]),
            ])->all();

            return $this->view('admin.glossary', ['terms' => $terms]);
        });
    }

    /**
     * The submissions nobody has decided on, and a form to edit and preview one.
     */
    public function pending(): void {
        $this->page('admin_glossary_pending', function () {
            global $THEUSER;

            $this->decide((int) $THEUSER->user_id());
            $pending = Editqueue::pending()->get();
            $edit = self::input('modify') === '' ? null : $pending->firstWhere('edit_id', (int) self::input('modify'));

            if ($edit === null) {
                return $this->view('admin.glossary_pending', [
                    'edit' => null,
                    'pending' => $pending->map(fn (Editqueue $item): array => [
                        'id' => $item->edit_id,
                        'title' => $item->title,
                        'body' => $item->body,
                        'user' => trim($item->firstname . ' ' . $item->lastname),
                        'approve_url' => $this->adminUrl('glossary_pending.php', ['approve' => $item->edit_id]),
                        'modify_url' => $this->adminUrl('glossary_pending.php', ['modify' => $item->edit_id]),
                        'decline_url' => $this->adminUrl('glossary_pending.php', ['decline' => $item->edit_id]),
                    ])->all(),
                    'form_url' => $this->adminUrl('glossary_pending.php'),
                ]);
            }

            $preview = self::input('previewterm') !== '';
            $title = $preview ? self::input('g') : (string) $edit->title;
            $body = $preview ? self::input('definition') : (string) $edit->body;

            return $this->view('admin.glossary_pending', [
                'edit' => [
                    'id' => $edit->edit_id,
                    'user_id' => $edit->user_id,
                    'title' => $title,
                    'body' => $body,
                    'wiki_link' => 'https://en.wikipedia.org/wiki/' . rawurlencode(strtr($title, ' ', '_')),
                    'wiki_guess' => self::input('wikiguess') !== '',
                    'preview_html' => self::linked(self::glossary(), $body),
                ],
                'form_url' => $this->adminUrl('glossary_pending.php'),
            ]);
        });
    }

    /**
     * Approves or declines the submission a moderator chose, once the admin check has passed.
     *
     * Approve can be one id or, from the form's checkboxes, a list of them.
     */
    private function decide(int $editor_id): void {
        $approve = $_POST['approve'] ?? $_GET['approve'] ?? null;
        $decline = (int) self::input('decline');
        if ($approve !== null) {
            foreach (Editqueue::pending()->whereIn('editqueue.edit_id', array_map('intval', (array) $approve))->get() as $item) {
                $item->approve($editor_id);
            }
        } elseif ($decline > 0) {
            Editqueue::pending()->where('editqueue.edit_id', $decline)->get()->each->decline($editor_id);
        }
    }

    /**
     * The legacy glossary, which links a body's terms to their definitions.
     */
    private static function glossary(): \GLOSSARY {
        return new \GLOSSARY(['sort' => 'regexp_replace']);
    }

    /**
     * A term's body with the other terms in it linked, after stripping all but the few tags people may use.
     *
     * This is the only HTML a view prints without escaping it.
     */
    private static function linked(\GLOSSARY $glossary, string $body): string {
        return $glossary->glossarise(filter_user_input($body, 'comment'), 0, 1);
    }

    /**
     * A request value from the form or the query string, as text.
     */
    private static function input(string $name): string {
        return (string) ($_POST[$name] ?? $_GET[$name] ?? '');
    }

}
