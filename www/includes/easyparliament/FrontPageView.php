<?php

/**
 * @file
 * Plain view-model helpers for the front page (www/docs/index.php) - the same split
 * as HansardSpeechView.php: pure functions in, plain data out, no DB/global access,
 * no direct echo/print. The Plates templates under resources/views/front/ are the
 * only things that turn these into markup.
 */

/**
 *
 */
class FrontPageView {

    /**
     * The feature row's first card - "Your Representative" (a resolved MP) or "Track
     * Your Reps" (the postcode form). $member is null when there's no MP to show yet
     * (no constituency set, or the resolved MEMBER wasn't valid) - the caller
     * (index.php) does the $THEUSER/MEMBER lookup, a real DB access; this only
     * decides what to display once that's already resolved, same division of labour
     * as HansardSpeechView::forSpeech() taking an already-fetched $row['speaker'].
     *
     * $member, when not null, is ['first_name' => ..., 'last_name' => ...,
     * 'still_in_house' => bool] - still_in_house false is what index.php's own
     * $left_house[1]['date'] != '9999-12-31' check meant: they've since left.
     *
     * @return array<string, mixed>
     *   The block's mode ('known'/'form') plus whichever fields that mode needs.
     */
    public static function mpBlock(?array $member, string $mpUrl, string $changeUrl): array {
        if ($member === null) {
            return ['mode' => 'form', 'mpUrl' => $mpUrl];
        }
        return [
            'mode' => 'known',
            'mpName' => $member['first_name'] . ' ' . $member['last_name'],
            'former' => $member['still_in_house'] ? '' : 'former',
            'mpUrl' => $mpUrl,
            'changeUrl' => $changeUrl,
        ];
    }

    /**
     * The feature row's third card ("Get Free Email Alerts") message - whether
     * they arrived with a ?keyword= (eg from an email alert link) or not.
     */
    public static function emailAlertText(?string $keyword): string {
        if ($keyword) {
            return "Get notified when '" . $keyword . "' is mentioned in Parliament.";
        }
        return 'Sign up to get an email whenever your representative speaks or a keyword you care about is mentioned.';
    }

    /**
     * Where that same card links to. htmlspecialchars(), not urlencode() - matches
     * the pre-extraction code exactly (www/docs/index.php's old email_alert_url()) -
     * not obviously correct for a query value with URL-reserved characters in it,
     * but not this refactor's place to silently change existing behaviour.
     */
    public static function emailAlertUrl(?string $keyword): string {
        if ($keyword) {
            return WEBPATH . 'alert?keyword=' . htmlspecialchars($keyword) . '&only=1';
        }
        return WEBPATH . 'alert/';
    }

    /**
     * The hero search box's "Popular searches today: ..." line - null when there are
     * none to show at all (the old code printed nothing in that case). $popularSearch
     * is one row from SEARCHLOG::popular_recent() - 'display' is already
     * pre-built, safe-to-echo-raw HTML (same trust the old code gave it, no
     * htmlspecialchars applied there either).
     *
     * Fits as many whole searches as add up to $maxChars total (by their plain
     * 'visible_name' length, not the HTML 'display' one) - a search that alone
     * would blow the budget is skipped, not truncated mid-word, same as before.
     *
     * @param array<int, array<string, string>> $popularSearches
     *   Rows from SEARCHLOG::popular_recent(), most popular first.
     * @param int $maxChars
     *   The character budget - matches the old hardcoded 32.
     */
    public static function popularSearchesLabel(array $popularSearches, int $maxChars = 32): ?string {
        if (count($popularSearches) == 0) {
            return null;
        }

        $lenTotal = 0;
        $correctAmount = [];
        foreach ($popularSearches as $popularSearch) {
            $len = strlen($popularSearch['visible_name']);
            if ($lenTotal + $len > $maxChars) {
                continue;
            }
            $lenTotal += $len;
            $correctAmount[] = $popularSearch['display'];
        }

        return implode(', ', $correctAmount);
    }

}
