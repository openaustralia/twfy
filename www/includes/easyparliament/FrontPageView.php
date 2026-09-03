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
     * they arrived with a ?keyword= (eg from an email alert link) or not. Checked
     * with !== null/'', not truthiness - the literal string "0" is a real, if
     * unlikely, keyword someone might search for, and truthiness would treat it the
     * same as no keyword at all.
     */
    public static function emailAlertText(?string $keyword): string {
        if ($keyword !== null && $keyword !== '') {
            return "Get notified when '" . $keyword . "' is mentioned in Parliament.";
        }
        return 'Sign up to get an email whenever your representative speaks or a keyword you care about is mentioned.';
    }

    /**
     * Where that same card links to. rawurlencode() the keyword into the query
     * string - htmlspecialchars() here (matching the pre-extraction code,
     * www/docs/index.php's old email_alert_url()) was HTML-escaping, not URL-
     * encoding: a keyword containing "&" survived as the literal entity "&amp;",
     * which the browser decodes straight back to "&" when it resolves the href -
     * splitting the query string and letting a keyword like "x&admin=1" inject an
     * extra param. This is a plain URL (feature-row.php's <a href> applies $this->e()
     * itself, same as every other URL this view-model returns), so HTML-escaping
     * doesn't belong here at all.
     */
    public static function emailAlertUrl(?string $keyword): string {
        if ($keyword !== null && $keyword !== '') {
            return WEBPATH . 'alert?keyword=' . rawurlencode($keyword) . '&only=1';
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

        // Genuinely possible, not just theoretical: every one of the day's popular
        // searches individually longer than $maxChars leaves this empty even though
        // $popularSearches itself wasn't - null here too, or the caller renders an
        // empty "Popular searches today: " line with nothing after the colon
        // (Sentry caught this - www/resources/views/front/search-hero.php checks
        // !== null, which is true for '').
        if (count($correctAmount) == 0) {
            return null;
        }

        return implode(', ', $correctAmount);
    }

    /**
     * The distinct topics within one "Latest Activity" item (eg the actual bills
     * debated under a "Bills" heading, or the committees reported on under
     * "Committees") - real Hansard subsection titles, not an inferred/generated
     * summary, each one linking to its own page (so "all the titles" on the front
     * page are real links, not just the item's own no-longer-existing whole-card
     * one). $subsections is each subsection's own body text + url, in order (see
     * www/docs/index.php's own latest_activity_items()) - title typically "<name>;
     * <stage>" (eg "Migration Amendment Bill 2026; Second Reading"). The same bill
     * usually appears more than once across a sitting day's stages, so this strips
     * the "; <stage>" suffix (the text after the *last* semicolon - some titles
     * legitimately contain their own semicolons before that, eg a multi-bill
     * cognate group) before deduping on the result, keeping first-seen order and
     * that occurrence's own url (its earliest stage that day). A title with no
     * semicolon at all (eg a bare "Rearrangement"/"Withdrawal" procedural item) is
     * kept whole.
     *
     * @param array<int, array{title: string, url: string}> $subsections
     *   'title' is already-safe HTML (same source as $item['title']) - not
     *   re-escaped here.
     * @param int $limit
     *   How many distinct topics to keep - the rest are only reflected in
     *   'moreCount'.
     *
     * @return array{topics: array<int, array{title: string, url: string}>, moreCount: int}
     *   The capped topic list and how many further distinct topics didn't fit.
     */
    public static function summarizeTopics(array $subsections, int $limit): array {
        $topics = [];
        $seenTitles = [];
        foreach ($subsections as $subsection) {
            $title = $subsection['title'];
            // $title is pre-escaped HTML and can carry an entity of its own (eg
            // "Survivors &amp; Mates Support Network" - epobject.body, confirmed the
            // same in the live DB) - a bare strrpos() for the stage-suffix separator
            // can find that entity's own closing ';' instead of a real one, truncating
            // mid-entity ("Survivors &amp"). Masking entities out first (same length,
            // so offsets into $title still line up) makes strrpos() only ever find a
            // genuine separator. Sentry finding on #228.
            $maskedTitle = preg_replace_callback(
                '/&(?:#\d+|#x[0-9a-fA-F]+|[a-zA-Z][a-zA-Z0-9]*);/',
                fn($m) => str_repeat('#', strlen($m[0])),
                $title
            );
            $lastSemicolon = strrpos($maskedTitle, ';');
            $topicTitle = $lastSemicolon === false ? $title : trim(substr($title, 0, $lastSemicolon));
            if (isset($seenTitles[$topicTitle])) {
                continue;
            }
            $seenTitles[$topicTitle] = true;
            $topics[] = ['title' => $topicTitle, 'url' => $subsection['url']];
        }

        return [
            'topics' => array_slice($topics, 0, $limit),
            'moreCount' => max(0, count($topics) - $limit),
        ];
    }

    /**
     * Each distinct speaker's *first* speech within one "Latest Activity" item,
     * keyed by speaker_id, in first-seen order - www/docs/index.php's own
     * latest_activity_items() fetches every htype=12 row in one section ordered by
     * hpos, so the first occurrence per speaker_id here is genuinely their first
     * speech in that section.
     *
     * @param array<int, array{speaker_id: int|string, speech_gid: string, subsection_gid: string}> $speechRows
     *   Every htype=12 row's speaker_id/gid/parent-subsection gid, in hpos order -
     *   a later row for a speaker_id already seen is a later speech by the same
     *   person and is skipped, not overwritten.
     *
     * @return array<int|string, array{speech_gid: string, subsection_gid: string}>
     *   Each speaker's first speech, keyed by speaker_id.
     */
    public static function firstSpeechBySpeaker(array $speechRows): array {
        $firstSpeechBySpeaker = [];
        foreach ($speechRows as $speechRow) {
            $speakerId = $speechRow['speaker_id'];
            if (!isset($firstSpeechBySpeaker[$speakerId])) {
                $firstSpeechBySpeaker[$speakerId] = [
                    'speech_gid' => $speechRow['speech_gid'],
                    'subsection_gid' => $speechRow['subsection_gid'],
                ];
            }
        }
        return $firstSpeechBySpeaker;
    }

    /**
     * One "Latest Activity" item, from values www/docs/index.php's own
     * latest_activity_items() has already resolved (some via real DB lookups,
     * eg $speakers) - moreSpeakersCount here so the caller doesn't also have to
     * import its "count($speakers), not count($shownSpeakerIds)" reasoning
     * (see that function's own comment on why).
     *
     * @param string $title
     *   Already-safe HTML (same source as summarizeTopics()'s own subsection
     *   titles) - not re-escaped here.
     * @param array<int, HansardSpeakerRosterEntry> $speakers
     *   Only the ones actually resolved/shown - see firstSpeechBySpeaker()'s
     *   caller for why a lookup can come back short of $totalSpeakers.
     * @param int $totalSpeakers
     *   Every distinct speaker in the section, whether or not their lookup
     *   made it into $speakers - count($firstSpeechBySpeaker) at the call site.
     * @param array{topics: array<int, array{title: string, url: string}>, moreCount: int} $topicsSummary
     *   summarizeTopics()'s own return shape.
     */
    public static function buildActivityItem(string $title, array $speakers, int $totalSpeakers, array $topicsSummary): array {
        return [
            'title' => $title,
            'speakers' => $speakers,
            'moreSpeakersCount' => max(0, $totalSpeakers - count($speakers)),
            'topics' => $topicsSummary['topics'],
            'moreTopicsCount' => $topicsSummary['moreCount'],
        ];
    }

}
