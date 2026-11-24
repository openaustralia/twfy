<?php

namespace OpenAustralia\TWFY\Pages;

use OpenAustralia\TWFY\Models\Editqueue;

/**
 * A visitor's suggestion for the glossary, which waits in the edit queue for a moderator.
 */
final class TermSubmission {

    /**
     * How many seconds a person must wait between suggestions, to stop accidental repeats.
     */
    public const FLOOD_SECONDS = 20;

    /**
     * The edit type of a glossary term in the edit queue.
     */
    private const TERM = 2;

    /**
     * Queues a term, or says why it can't be.
     *
     * @param int $user_id
     *   Who is suggesting it.
     * @param string $title
     *   The phrase.
     * @param string $body
     *   The definition.
     *
     * @return array{ok: bool, message: string, title: string}
     *   Whether it was queued, a message for the person if it was not, and the phrase as it was kept.
     */
    public static function submit(int $user_id, string $title, string $body): array {
        $title = filter_user_input($title, 'strict');
        $body = filter_user_input($body, 'comment');

        $validator = twfy_laravel()->make('validator')->make(
            ['title' => $title, 'body' => $body],
            ['title' => 'required|max:255', 'body' => 'required|max:65000'],
            [
                'title.required' => 'Sorry, you can\'t define a term without a title.',
                'title.max' => 'Sorry, that phrase is too long. Please keep it under 255 characters.',
                'body.required' => 'You haven\'t entered a definition.',
                'body.max' => 'Sorry, that definition is too long.',
            ]
        );
        if ($validator->fails()) {
            return ['ok' => false, 'message' => $validator->errors()->first(), 'title' => $title];
        }
        if (Editqueue::where('user_id', $user_id)->where('submitted', '>', date('Y-m-d H:i:s', time() - self::FLOOD_SECONDS))->exists()) {
            return ['ok' => false, 'message' => 'Sorry, we limit people to one term every ' . self::FLOOD_SECONDS . ' seconds, to help prevent duplicates. Please try again in a moment.', 'title' => $title];
        }

        Editqueue::create(['user_id' => $user_id, 'edit_type' => self::TERM, 'title' => $title, 'body' => $body, 'submitted' => date('Y-m-d H:i:s')]);

        return ['ok' => true, 'message' => '', 'title' => $title];
    }

}
