<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/FrontPageView.php';

/**
 * FrontPageView (see www/docs/index.php) is the front page's view-model layer -
 * plain data in, plain data out, no DB/global access, no direct echo/print. Same
 * split as HansardSpeechView.php for the debate transcript page (see
 * openaustralia/openaustralia#939).
 */
class FrontPageViewTest extends TestCase {

    /**
     *
     */
    public function test_mpBlock_returns_the_form_mode_when_there_is_no_member() {
        $block = FrontPageView::mpBlock(null, '/mp/', '/user/changepc/');

        $this->assertSame('form', $block['mode']);
        $this->assertSame('/mp/', $block['mpUrl']);
    }

    /**
     *
     */
    public function test_mpBlock_returns_the_known_mode_with_the_members_name() {
        $member = ['first_name' => 'Zali', 'last_name' => 'Steggall', 'still_in_house' => true];

        $block = FrontPageView::mpBlock($member, '/mp/?m=1', '/user/changepc/');

        $this->assertSame('known', $block['mode']);
        $this->assertSame('Zali Steggall', $block['mpName']);
        $this->assertSame('/mp/?m=1', $block['mpUrl']);
        $this->assertSame('/user/changepc/', $block['changeUrl']);
    }

    /**
     *
     */
    public function test_mpBlock_labels_a_former_member_as_former() {
        $current = FrontPageView::mpBlock(
            ['first_name' => 'Zali', 'last_name' => 'Steggall', 'still_in_house' => true],
            '/mp/', '/user/changepc/'
        );
        $former = FrontPageView::mpBlock(
            ['first_name' => 'Zali', 'last_name' => 'Steggall', 'still_in_house' => false],
            '/mp/', '/user/changepc/'
        );

        $this->assertSame('', $current['former']);
        $this->assertSame('former', $former['former']);
    }

    /**
     *
     */
    public function test_emailAlertText_mentions_the_keyword_when_present() {
        $this->assertSame(
            "Get notified when 'budget' is mentioned in Parliament.",
            FrontPageView::emailAlertText('budget')
        );
    }

    /**
     *
     */
    public function test_emailAlertText_falls_back_to_the_generic_message() {
        $this->assertSame(
            'Sign up to get an email whenever your representative speaks or a keyword you care about is mentioned.',
            FrontPageView::emailAlertText(null)
        );
        $this->assertSame(
            'Sign up to get an email whenever your representative speaks or a keyword you care about is mentioned.',
            FrontPageView::emailAlertText('')
        );
    }

    /**
     *
     */
    public function test_emailAlertUrl_includes_the_keyword_when_present() {
        $this->assertSame('/alert?keyword=budget&only=1', FrontPageView::emailAlertUrl('budget'));
    }

    /**
     * A keyword containing "&" has to be URL-encoded, not HTML-escaped - HTML-
     * escaping ("&" -> "&amp;") survives into the rendered href, but the browser
     * decodes that entity straight back to a literal "&" when it resolves the URL,
     * splitting the query string. rawurlencode() ("&" -> "%26") is what actually
     * keeps it inside the keyword value.
     */
    public function test_emailAlertUrl_url_encodes_a_keyword_containing_reserved_characters() {
        $this->assertSame(
            '/alert?keyword=x%26admin%3D1&only=1',
            FrontPageView::emailAlertUrl('x&admin=1')
        );
    }

    /**
     *
     */
    public function test_emailAlertUrl_falls_back_to_the_plain_alert_page() {
        $this->assertSame('/alert/', FrontPageView::emailAlertUrl(null));
        $this->assertSame('/alert/', FrontPageView::emailAlertUrl(''));
    }

    /**
     * A search for the literal keyword "0" is real, if unlikely - a truthiness
     * check would treat it the same as no keyword at all.
     */
    public function test_a_keyword_of_literal_zero_is_not_treated_as_absent() {
        $this->assertSame(
            "Get notified when '0' is mentioned in Parliament.",
            FrontPageView::emailAlertText('0')
        );
        $this->assertSame('/alert?keyword=0&only=1', FrontPageView::emailAlertUrl('0'));
    }

    /**
     *
     */
    public function test_popularSearchesLabel_returns_null_when_there_are_none() {
        $this->assertNull(FrontPageView::popularSearchesLabel([]));
    }

    /**
     * Caught by Sentry's automated review on the PR: genuinely possible, not just
     * theoretical - every one of the day's popular searches individually longer than
     * the budget leaves $correctAmount empty even though $popularSearches itself
     * wasn't, and search-hero.php's !== null check doesn't catch a bare ''.
     */
    public function test_popularSearchesLabel_returns_null_when_every_search_is_too_long_to_fit() {
        $searches = [$this->popularSearch('this-search-term-alone-is-already-too-long', 'x')];

        $this->assertNull(FrontPageView::popularSearchesLabel($searches, 10));
    }

    /**
     *
     */
    public function test_popularSearchesLabel_joins_the_display_html_of_every_search_that_fits() {
        $searches = [
            $this->popularSearch('house', '<a href="/search/?s=house">house</a>'),
            $this->popularSearch('budget', '<a href="/search/?s=budget">budget</a>'),
        ];

        $label = FrontPageView::popularSearchesLabel($searches);

        $this->assertSame('<a href="/search/?s=house">house</a>, <a href="/search/?s=budget">budget</a>', $label);
    }

    /**
     * "house" (5) + "budget" (6) + "environment" (11) = 22, all within the default
     * 32-char budget - but adding a fourth ("infrastructure", 14) would push the
     * total to 36, over budget, so it's skipped even though it's not the first one
     * that would have fit alone.
     */
    public function test_popularSearchesLabel_skips_a_search_that_would_exceed_the_budget() {
        $searches = [
            $this->popularSearch('house', 'house'),
            $this->popularSearch('budget', 'budget'),
            $this->popularSearch('environment', 'environment'),
            $this->popularSearch('infrastructure', 'infrastructure'),
        ];

        $label = FrontPageView::popularSearchesLabel($searches);

        $this->assertSame('house, budget, environment', $label);
    }

    /**
     * A later, shorter search can still fit after an earlier one was skipped -
     * skipping isn't the same as stopping.
     */
    public function test_popularSearchesLabel_keeps_checking_after_skipping_one_that_is_too_long() {
        $searches = [
            $this->popularSearch('this-is-a-very-long-search-term-indeed', 'long'),
            $this->popularSearch('house', 'house'),
        ];

        $label = FrontPageView::popularSearchesLabel($searches, 10);

        $this->assertSame('house', $label);
    }

    /**
     * @return array<string, string>
     */
    private function popularSearch(string $visibleName, string $display): array {
        return ['visible_name' => $visibleName, 'display' => $display];
    }

    /**
     *
     */
    public function test_summarizeTopics_strips_the_stage_suffix_after_the_last_semicolon() {
        $topics = FrontPageView::summarizeTopics(
            [$this->subsection('Migration Amendment Bill 2026; Second Reading')],
            5
        );

        $this->assertSame('Migration Amendment Bill 2026', $topics['topics'][0]['title']);
    }

    /**
     * The same bill usually appears more than once across a sitting day's stages -
     * deduping on the stripped title collapses them to one entry, keeping that
     * first occurrence's own url (its earliest stage that day).
     */
    public function test_summarizeTopics_dedupes_the_same_bill_across_multiple_stages() {
        $topics = FrontPageView::summarizeTopics([
            $this->subsection('Migration Amendment Bill 2026; Second Reading', '/debates/?id=1'),
            $this->subsection('Migration Amendment Bill 2026; Third Reading', '/debates/?id=2'),
        ], 5);

        $this->assertCount(1, $topics['topics']);
        $this->assertSame('/debates/?id=1', $topics['topics'][0]['url']);
    }

    /**
     * Some titles legitimately contain their own semicolons before the stage
     * suffix (eg a multi-bill cognate group) - only the text after the *last*
     * semicolon is stripped.
     */
    public function test_summarizeTopics_only_strips_after_the_last_semicolon() {
        $topics = FrontPageView::summarizeTopics(
            [$this->subsection('News Journalism Payments Bill 2026, News Media Bargaining Charge Bill 2026; First Reading')],
            5
        );

        $this->assertSame(
            'News Journalism Payments Bill 2026, News Media Bargaining Charge Bill 2026',
            $topics['topics'][0]['title']
        );
    }

    /**
     * A title can carry its own HTML entity (eg an "&amp;" in an organisation's
     * name) with no real stage suffix at all - the entity's own closing ';' must
     * not be mistaken for the separator, or the title gets truncated mid-entity
     * (eg "Parragirls, Survivors &amp" - Sentry finding on #228).
     */
    public function test_summarizeTopics_does_not_split_on_the_semicolon_inside_an_entity() {
        $topics = FrontPageView::summarizeTopics(
            [$this->subsection('Parragirls, Survivors &amp; Mates Support Network')],
            5
        );

        $this->assertSame('Parragirls, Survivors &amp; Mates Support Network', $topics['topics'][0]['title']);
    }

    /**
     * A genuine stage-suffix semicolon must still be found (and everything after
     * it stripped) even when the title also carries an unrelated entity earlier on.
     */
    public function test_summarizeTopics_still_strips_a_real_stage_suffix_after_an_entity() {
        $topics = FrontPageView::summarizeTopics(
            [$this->subsection('Parragirls, Survivors &amp; Mates Support Network Bill 2026; Second Reading')],
            5
        );

        $this->assertSame('Parragirls, Survivors &amp; Mates Support Network Bill 2026', $topics['topics'][0]['title']);
    }

    /**
     * A bare procedural item (eg "Rearrangement"/"Withdrawal") has no "; <stage>"
     * suffix at all - kept whole, not mangled.
     */
    public function test_summarizeTopics_keeps_a_title_with_no_semicolon_whole() {
        $topics = FrontPageView::summarizeTopics([$this->subsection('Rearrangement')], 5);

        $this->assertSame('Rearrangement', $topics['topics'][0]['title']);
    }

    /**
     *
     */
    public function test_summarizeTopics_caps_at_the_limit_and_counts_the_rest() {
        $subsections = [
            $this->subsection('Bill One; Second Reading'),
            $this->subsection('Bill Two; Second Reading'),
            $this->subsection('Bill Three; Second Reading'),
        ];

        $topics = FrontPageView::summarizeTopics($subsections, 2);

        $this->assertCount(2, $topics['topics']);
        $this->assertSame(1, $topics['moreCount']);
    }

    /**
     *
     */
    public function test_summarizeTopics_returns_a_zero_moreCount_when_nothing_was_cut() {
        $topics = FrontPageView::summarizeTopics([$this->subsection('Bill One; Second Reading')], 5);

        $this->assertSame(0, $topics['moreCount']);
    }

    /**
     * @return array{title: string, url: string}
     */
    private function subsection(string $title, string $url = '/debates/?id=1'): array {
        return ['title' => $title, 'url' => $url];
    }

    /**
     *
     */
    public function test_firstSpeechBySpeaker_keys_each_speech_by_its_speaker() {
        $bySpeaker = FrontPageView::firstSpeechBySpeaker([
            $this->speechRow(100931, 'g1', 'sub1'),
            $this->speechRow(100907, 'g2', 'sub1'),
        ]);

        $this->assertSame(['speech_gid' => 'g1', 'subsection_gid' => 'sub1'], $bySpeaker[100931]);
        $this->assertSame(['speech_gid' => 'g2', 'subsection_gid' => 'sub1'], $bySpeaker[100907]);
    }

    /**
     * $speechRows is ordered by hpos (the caller's own SQL ORDER BY) - a later row
     * for a speaker_id already seen is a later speech by the same person, kept out
     * so the first one isn't overwritten.
     */
    public function test_firstSpeechBySpeaker_keeps_only_the_first_speech_for_a_repeat_speaker() {
        $bySpeaker = FrontPageView::firstSpeechBySpeaker([
            $this->speechRow(100931, 'g1', 'sub1'),
            $this->speechRow(100931, 'g5', 'sub2'),
        ]);

        $this->assertCount(1, $bySpeaker);
        $this->assertSame(['speech_gid' => 'g1', 'subsection_gid' => 'sub1'], $bySpeaker[100931]);
    }

    /**
     *
     */
    public function test_firstSpeechBySpeaker_preserves_first_seen_order() {
        $bySpeaker = FrontPageView::firstSpeechBySpeaker([
            $this->speechRow(100907, 'g1', 'sub1'),
            $this->speechRow(100931, 'g2', 'sub1'),
        ]);

        $this->assertSame([100907, 100931], array_keys($bySpeaker));
    }

    /**
     *
     */
    public function test_buildActivityItem_assembles_the_item_from_already_resolved_values() {
        $topicsSummary = ['topics' => [['title' => 'Migration Amendment Bill', 'url' => '/x']], 'moreCount' => 2];

        $item = FrontPageView::buildActivityItem('Bills', ['speaker-a'], 1, $topicsSummary);

        $this->assertSame('Bills', $item['title']);
        $this->assertSame(['speaker-a'], $item['speakers']);
        $this->assertSame($topicsSummary['topics'], $item['topics']);
        $this->assertSame(2, $item['moreTopicsCount']);
    }

    /**
     * count($speakers), not $totalSpeakers - $totalSpeakers - the latter is attempted
     * lookups, not what's actually shown: a speaker lookup can come back empty and
     * get skipped, which would otherwise undercount "+N more" by however many
     * lookups failed. See www/docs/index.php's own latest_activity_items().
     */
    public function test_buildActivityItem_counts_more_speakers_against_the_shown_speakers_not_the_attempted_lookups() {
        $topicsSummary = ['topics' => [], 'moreCount' => 0];

        $item = FrontPageView::buildActivityItem('Bills', ['speaker-a'], 3, $topicsSummary);

        $this->assertSame(2, $item['moreSpeakersCount']);
    }

    /**
     * @return array{speaker_id: int, speech_gid: string, subsection_gid: string}
     */
    private function speechRow(int $speakerId, string $speechGid, string $subsectionGid): array {
        return ['speaker_id' => $speakerId, 'speech_gid' => $speechGid, 'subsection_gid' => $subsectionGid];
    }

}
