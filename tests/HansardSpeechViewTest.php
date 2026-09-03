<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/member.php';
require_once __DIR__ . '/../www/includes/easyparliament/HansardSpeechView.php';

if (!defined('TIMEFORMAT')) {
    define('TIMEFORMAT', 'g:i a');
}

/**
 * gid_to_anchor(), format_time() and member_full_name() are otherwise defined in
 * www/includes/utility.php - not required directly here because it unconditionally
 * (re)declares twfy_debug(), which tests/bootstrap.php already stubs; requiring both
 * is a fatal redeclaration. MemberUrlTest.php hits the same thing and takes the same
 * way out: small local copies of just the functions actually needed, guarded by
 * function_exists so nothing breaks if a later test run does load the real file.
 */
if (!function_exists('gid_to_anchor')) {

    function gid_to_anchor($gid) {
        return substr($gid, (strpos($gid, '.') + 1));
    }

}

if (!function_exists('format_time')) {

    function format_time($time, $format) {
        if (preg_match("/^(\d\d):(\d\d):(\d\d)$/", $time, $matches)) {
            [$string, $hour, $min, $sec] = $matches;
            return gmdate($format, gmmktime($hour, $min, $sec));
        }
        return "";
    }

}

if (!function_exists('member_full_name')) {

    function member_full_name($house, $title, $first_name, $last_name, $constituency) {
        if ($house == 1 || $house == 2 || $house == 3 || $house == 4) {
            $s = $first_name . ' ' . $last_name;
            if ($title) {
                $s = $title . ' ' . $s;
            }
            return $s;
        }
        return 'ERROR';
    }

}

// context_link()/generate_commentteaser() (called by forSpeech()/forProcedural()
// below) are stubbed in tests/bootstrap.php, not here - see that file's own
// comment on why.

/**
 * HansardSpeechView/HansardProceduralView/HansardSpeakerRosterEntry (see
 * HansardSpeechView.php) are the view-model layer for the Plates-rendered debate
 * transcript (hansard_gid.php, major 1/101 only) - plain data in, plain data out,
 * no $PAGE/DB calls of their own, which is what makes them worth unit testing
 * directly rather than only through a rendered page (see
 * openaustralia/openaustralia#939).
 */
class HansardSpeechViewTest extends TestCase {

    /**
     *
     */
    public function test_forSpeech_fills_in_speaker_fields_from_a_named_speaker() {
        $row = $this->speechRow([
            'speaker' => $this->speaker(['title' => 'Senator', 'first_name' => 'Penny', 'last_name' => 'Allman-Payne']),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('Senator Penny Allman-Payne', $view->speakerName);
        $this->assertSame('PA', $view->speakerInitials);
        $this->assertSame('/senator/?m=100931', $view->speakerUrl);
        $this->assertSame('Queensland, Australian Greens', $view->speakerDescription);
    }

    /**
     * _get_speaker() (hansardlist.php) only sets 'office' when the member holds one
     * on the debate's own date (moffice's date range) - appended after the
     * party/electorate line when present.
     */
    public function test_forSpeech_appends_the_office_when_the_speaker_holds_one() {
        $row = $this->speechRow([
            'speaker' => $this->speaker([
                'office' => [['dept' => '', 'position' => 'Minister for Health', 'pretty' => 'Minister for Health']],
            ]),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('Queensland, Australian Greens, Minister for Health', $view->speakerDescription);
    }

    /**
     * HANSARDLIST::_get_speaker() translates the raw member.party code
     * (SPK/CWM/DCWM/PRES/DPRES) into one of these hyphenated labels via
     * dbtypes.php's $parties before $speaker reaches here - a chair role still
     * has a constituency on file, but showing it here would be misleading, since
     * they're not speaking as their electorate's member while in the chair.
     */
    public function test_forSpeech_omits_the_constituency_for_a_chair_role() {
        foreach (['Speaker', 'Deputy-Speaker', 'President', 'Deputy-President'] as $chairRole) {
            $row = $this->speechRow([
                'speaker' => $this->speaker(['party' => $chairRole, 'constituency' => 'Queensland']),
            ]);

            $view = HansardSpeechView::forSpeech($row, $this->info(), true);

            $this->assertSame($chairRole, $view->speakerDescription, "for party '$chairRole'");
        }
    }

    /**
     * A caller could hand speakerDescription() an empty 'office' array (eg a test
     * fixture, or a future call site) rather than simply not setting the key -
     * isset() alone would still try to read $speaker['office'][0] and warn.
     */
    public function test_forSpeech_tolerates_an_empty_office_array() {
        $row = $this->speechRow([
            'speaker' => $this->speaker(['office' => []]),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('Queensland, Australian Greens', $view->speakerDescription);
    }

    /**
     * Every caller echoes speakerDescription raw, treating it as already-escaped -
     * a "&" in constituency or office needs to actually be escaped for that to be
     * true, not just one in $speaker['party'].
     */
    public function test_forSpeech_escapes_the_constituency_and_office_too_not_just_the_party() {
        $row = $this->speechRow([
            'speaker' => $this->speaker([
                'constituency' => 'Cook & Innisfail',
                'office' => [['dept' => '', 'position' => 'x', 'pretty' => 'Minister for A & B']],
            ]),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('Cook &amp; Innisfail, Australian Greens, Minister for A &amp; B', $view->speakerDescription);
    }

    /**
     * moffice.position sometimes already contains a named entity (eg "Minister for
     * Veterans&rsquo; Affairs" - 7 rows in db/seeds/data/moffice.csv, confirmed the
     * same in the live DB) - escaping without decoding first renders the entity
     * literally instead of the character it names. Ben Fairless's review on #227.
     */
    public function test_forSpeech_does_not_double_escape_an_office_holding_an_entity() {
        $row = $this->speechRow([
            'speaker' => $this->speaker([
                'constituency' => 'Kingsford Smith',
                'office' => [['dept' => '', 'position' => 'x', 'pretty' => 'Minister for Veterans&rsquo; Affairs']],
            ]),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertStringContainsString('Minister for Veterans&rsquo; Affairs', $view->speakerDescription);
        $this->assertStringNotContainsString('&amp;rsquo;', $view->speakerDescription);
    }

    /**
     * A chair role like "The Deputy Speaker" can have a person_id but no
     * member_id (see buildRoster()'s own comment on this) - speakerId falls back
     * to person_id so the roster still has a stable dedup key for them.
     */
    public function test_forSpeech_falls_back_to_person_id_when_member_id_is_absent() {
        $row = $this->speechRow([
            'speaker' => $this->speaker(['member_id' => null, 'person_id' => 555]),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('555', $view->speakerId);
    }

    /**
     *
     */
    public function test_forSpeech_leaves_speaker_fields_null_when_the_row_has_no_speaker() {
        $row = $this->speechRow(['speaker' => []]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertNull($view->speakerName);
        $this->assertNull($view->speakerUrl);
        $this->assertNull($view->speakerDescription);
        $this->assertNull($view->avatarUrl);
        $this->assertFalse($view->isCurrentSpeaker);
    }

    /**
     *
     */
    public function test_forSpeech_only_shows_the_timestamp_when_showTimestamp_is_true() {
        $row = $this->speechRow(['htime' => '14:26:00']);

        $shown = HansardSpeechView::forSpeech($row, $this->info(), true);
        $hidden = HansardSpeechView::forSpeech($row, $this->info(), false);

        $this->assertSame('2:26 pm', $shown->timestamp);
        $this->assertNull($hidden->timestamp);
    }

    /**
     *
     */
    public function test_forSpeech_treats_midnight_htime_as_no_time_at_all() {
        // "00:00:00" means the row's time genuinely isn't known, not literally
        // midnight - same convention the old stripe rendering used.
        $row = $this->speechRow(['htime' => '00:00:00']);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertNull($view->timestamp);
    }

    /**
     *
     */
    public function test_forSpeech_marks_the_debates_own_member_as_the_current_speaker() {
        $row = $this->speechRow([
            'speaker' => $this->speaker(['member_id' => 42]),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(['member_id' => 42]), true);

        $this->assertTrue($view->isCurrentSpeaker);
    }

    /**
     *
     */
    public function test_forSpeech_does_not_mark_a_different_member_as_the_current_speaker() {
        $row = $this->speechRow([
            'speaker' => $this->speaker(['member_id' => 42]),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(['member_id' => 99]), true);

        $this->assertFalse($view->isCurrentSpeaker);
    }

    /**
     *
     */
    public function test_forSpeech_detects_anonymous_interjections() {
        // The &#8212; entity, not a literal em-dash: real body text stores it this
        // way (confirmed against real data), not as the literal character.
        $row = $this->speechRow(['body' => '<p>Opposition senators interjecting&#8212;</p>']);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertTrue($view->isInterjection);
    }

    /**
     *
     */
    public function test_forSpeech_does_not_flag_an_ordinary_speech_as_an_interjection() {
        $row = $this->speechRow(['body' => '<p>I move that this bill be read a second time.</p>']);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertFalse($view->isInterjection);
    }

    /**
     * A named MP's own speech can legitimately mention the word "interjecting" in
     * its prose (eg quoting or describing someone else) - only the anonymous,
     * unresolved-speaker case should get the interjection treatment. Copilot
     * finding on #227.
     */
    public function test_forSpeech_does_not_flag_a_named_speakers_speech_as_an_interjection() {
        $row = $this->speechRow([
            'speaker' => $this->speaker(),
            'body' => '<p>The member opposite was interjecting throughout my speech.</p>',
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertFalse($view->isInterjection);
    }

    /**
     *
     */
    public function test_forSpeech_handles_a_null_body_without_a_typeerror() {
        $row = $this->speechRow(['body' => null]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertFalse($view->isInterjection);
    }

    /**
     *
     */
    public function test_forSpeech_marks_up_moved_motion_text() {
        $row = $this->speechRow(['body' => '<p pwmotiontext="moved">That the bill be read a second time.</p>']);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('<p class="moved">That the bill be read a second time.</p>', $view->bodyHtml);
    }

    /**
     *
     */
    public function test_forSpeech_marks_hansard_source_links_as_nofollow() {
        $row = $this->speechRow(['body' => '<p><a href="https://parlinfo.aph.gov.au/x">source</a></p>']);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('<p><a rel="nofollow" href="https://parlinfo.aph.gov.au/x">source</a></p>', $view->bodyHtml);
    }

    /**
     *
     */
    public function test_forSpeech_separates_adjacent_paragraphs_with_a_space() {
        $row = $this->speechRow(['body' => '<p>First.</p><p>Second.</p>']);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('<p>First.</p> <p>Second.</p>', $view->bodyHtml);
    }

    /**
     * A real example from senate 2026-08-19.164: a stray, empty "<i/>" left between
     * two real <i>...</i> phrases by the parser's XML-to-HTML conversion. Browsers
     * don't treat "<i/>" as self-closing - left in, it opens an <i> that never closes,
     * silently italicising everything on the page that follows it.
     */
    public function test_forSpeech_strips_stray_self_closed_italic_tags() {
        $row = $this->speechRow(['body' => '<p><i>Civil penalty provision</i> <i/> <i>authorising conduct</i></p>']);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('<p><i>Civil penalty provision</i>  <i>authorising conduct</i></p>', $view->bodyHtml);
    }

    /**
     *
     */
    public function test_forSpeech_only_sets_a_source_url_when_the_row_has_a_non_empty_one() {
        $withSource = HansardSpeechView::forSpeech($this->speechRow(['source_url' => 'https://example.org/x']), $this->info(), true);
        $emptySource = HansardSpeechView::forSpeech($this->speechRow(['source_url' => '']), $this->info(), true);
        $noSourceKey = $this->speechRow([]);
        unset($noSourceKey['source_url']);
        $missingSource = HansardSpeechView::forSpeech($noSourceKey, $this->info(), true);

        $this->assertSame('https://example.org/x', $withSource->sourceUrl);
        $this->assertSame('Hansard source', $withSource->sourceLabel);
        $this->assertNull($emptySource->sourceUrl);
        $this->assertNull($missingSource->sourceUrl);
    }

    /**
     * $row['commentsurl'] only gets set for htype-12 rows (see hansard_gid.php's own
     * $input['amount'] building) - permalinkUrl is null when it's genuinely absent,
     * not just falsy, since an empty string would still be a link to somewhere.
     */
    public function test_forSpeech_sets_the_permalink_only_when_the_row_has_a_commentsurl() {
        $withUrl = HansardSpeechView::forSpeech($this->speechRow(['commentsurl' => '/debates/?id=2026-08-18.108.2']), $this->info(), true);
        $noUrlKey = $this->speechRow([]);
        unset($noUrlKey['commentsurl']);
        $withoutUrl = HansardSpeechView::forSpeech($noUrlKey, $this->info(), true);

        $this->assertSame('/debates/?id=2026-08-18.108.2', $withUrl->permalinkUrl);
        $this->assertNull($withoutUrl->permalinkUrl);
    }

    /**
     *
     */
    public function test_forSpeech_passes_the_row_and_major_through_to_the_sidebar_helpers() {
        $row = $this->speechRow(['gid' => '2026-08-18.108.5']);

        $view = HansardSpeechView::forSpeech($row, $this->info(['major' => 101]), true);

        $this->assertSame('<context-link:2026-08-18.108.5>', $view->contextLinkHtml);
        $this->assertSame('<comment-teaser:2026-08-18.108.5:101>', $view->commentTeaserHtml);
    }

    /**
     *
     */
    public function test_forProcedural_builds_a_minimal_view_with_no_speaker_fields_at_all() {
        $row = ['gid' => '2026-08-18.108.1', 'body' => '<p>The Senate divided.</p>'];

        $view = HansardProceduralView::forProcedural($row, $this->info(['major' => 101]));

        $this->assertSame('g108.1', $view->id);
        $this->assertSame('<p>The Senate divided.</p>', $view->bodyHtml);
        $this->assertSame('<context-link:2026-08-18.108.1>', $view->contextLinkHtml);
        $this->assertSame('<comment-teaser:2026-08-18.108.1:101>', $view->commentTeaserHtml);
    }

    /**
     * forProcedural() runs bodyHtml through the same cleanBody() as forSpeech() (see
     * that class's test for why the <i/> case matters) - procedural rows are short,
     * but nothing rules out the same parser artifact turning up in one.
     */
    public function test_forProcedural_also_cleans_the_body() {
        $row = ['gid' => '2026-08-18.108.1', 'body' => '<p><i>Term</i> <i/> <i>other</i></p>'];

        $view = HansardProceduralView::forProcedural($row, $this->info());

        $this->assertSame('<p><i>Term</i>  <i>other</i></p>', $view->bodyHtml);
    }

    /**
     *
     */
    public function test_buildRoster_is_empty_for_procedural_only_pages() {
        $items = [
            HansardProceduralView::forProcedural(['gid' => '2026-08-18.108.1', 'body' => 'x'], $this->info()),
        ];

        $this->assertSame([], HansardSpeechView::buildRoster($items));
    }

    /**
     *
     */
    public function test_buildRoster_dedupes_a_repeat_speaker_onto_one_entry() {
        $items = [
            $this->speechView('Larissa Waters', '/senator/?m=100884', 'Word word word word word.'),
            $this->speechView('Larissa Waters', '/senator/?m=100884', 'Two more words.'),
        ];

        $roster = HansardSpeechView::buildRoster($items);

        $this->assertCount(1, $roster);
        $this->assertSame('Larissa Waters', $roster[0]->name);
        $this->assertSame(2, $roster[0]->speechCount);
        $this->assertSame(8, $roster[0]->wordCount);
    }

    /**
     * Two different people can both be "The Deputy Speaker" in the same debate if
     * the chair changes hands mid-sitting - neither has a speakerUrl (a chair role,
     * not a byline), so deduping on speakerUrl-or-name alone would merge two
     * different people's word/speech counts into one entry. speakerId (member_id) is
     * what actually tells them apart.
     */
    public function test_buildRoster_does_not_merge_two_different_speakers_sharing_a_chair_title() {
        $items = [
            $this->speechView('The Deputy Speaker', '', 'Order, order.', null, null, null, '111'),
            $this->speechView('The Deputy Speaker', '', 'The member will resume their seat.', null, null, null, '222'),
        ];

        $roster = HansardSpeechView::buildRoster($items);

        $this->assertCount(2, $roster);
    }

    /**
     *
     */
    public function test_buildRoster_orders_by_word_count_not_speech_count() {
        // Six short interjections, but fewer words in total than one real speech.
        $items = [
            $this->speechView('Chatty Backbencher', '/mp/?m=1', 'Hear hear!'),
            $this->speechView('Chatty Backbencher', '/mp/?m=1', 'Shame!'),
            $this->speechView('Chatty Backbencher', '/mp/?m=1', 'Rubbish!'),
            $this->speechView(
                'Minister for Everything',
                '/mp/?m=2',
                'This is a long and considered ministerial answer with a great many words in it indeed.'
            ),
        ];

        $roster = HansardSpeechView::buildRoster($items);

        $this->assertSame('Minister for Everything', $roster[0]->name);
        $this->assertSame('Chatty Backbencher', $roster[1]->name);
    }

    /**
     *
     */
    public function test_buildRoster_carries_the_avatar_and_description_from_the_first_appearance() {
        $items = [
            $this->speechView('Katy Gallagher', '/senator/?m=100907', 'First speech.', 'ALP, Australian Capital Territory', '/images/mpsL/10838.jpg'),
        ];

        $roster = HansardSpeechView::buildRoster($items);

        $this->assertSame('ALP, Australian Capital Territory', $roster[0]->description);
        $this->assertSame('/images/mpsL/10838.jpg', $roster[0]->avatarUrl);
    }

    /**
     *
     */
    public function test_buildRoster_carries_the_initials_from_the_first_appearance() {
        $items = [
            $this->speechView('Katy Gallagher', '/senator/?m=100907', 'First speech.', null, null, 'KG'),
        ];

        $roster = HansardSpeechView::buildRoster($items);

        $this->assertSame('KG', $roster[0]->initials);
    }

    /**
     * The plain case: prev/up/next all present, 'up' relabelled to point at the
     * day-listing page instead of $DATA->page_metadata()'s own "See the whole
     * debate" (the parent subsection/section's page).
     */
    public function test_buildNextPrev_relabels_the_up_link_to_the_day_listing_page() {
        $nextPrev = HansardSpeechView::buildNextPrev([
            'prev' => ['body' => 'Motions', 'url' => '/debates/?id=1', 'title' => 'Previous debate'],
            'up' => ['body' => 'See the whole debate', 'url' => '/debates/?id=2'],
            'next' => ['body' => 'Adjournment', 'url' => '/debates/?id=3', 'title' => 'Next debate'],
        ], 'House debates', '/debates/?d=2026-08-20');

        $this->assertSame('Motions', $nextPrev['prev']['label']);
        $this->assertSame('All House debates on this day', $nextPrev['up']['label']);
        $this->assertSame('/debates/?d=2026-08-20', $nextPrev['up']['url']);
        $this->assertSame('All House debates on this day', $nextPrev['up']['title']);
        $this->assertSame('Adjournment', $nextPrev['next']['label']);
    }

    /**
     * An htype-10/11 neighbour's title is a heading's raw body, which can already
     * contain an entity (eg "Survivors &amp; Mates Support Network" -
     * epobject.body, confirmed the same in the live DB) - main only ever put this
     * in a title="" tooltip attribute (browser-decoded either way), but this card
     * shows it as visible text, escaped once by pagination.php's $this->e(). Ben
     * Fairless's review on #227.
     */
    public function test_buildNextPrev_decodes_an_entity_in_a_neighbouring_debate_title() {
        $nextPrev = HansardSpeechView::buildNextPrev([
            'prev' => [
                'body' => 'Previous debate',
                'url' => '/debates/?id=a',
                'title' => 'Parragirls, Survivors &amp; Mates Support Network',
            ],
        ], 'House debates', '/debates/?d=2026-08-18');

        $this->assertSame('Parragirls, Survivors & Mates Support Network', $nextPrev['prev']['title']);
    }

    /**
     * Each of prev/up/next is independently optional - the very first debate ever
     * recorded has no 'prev' at all, for instance.
     */
    public function test_buildNextPrev_omits_directions_with_no_data() {
        $nextPrev = HansardSpeechView::buildNextPrev([
            'next' => ['body' => 'Adjournment', 'url' => '/debates/?id=3'],
        ], 'House debates', '/debates/?d=2026-08-20');

        $this->assertArrayNotHasKey('prev', $nextPrev);
        $this->assertArrayNotHasKey('up', $nextPrev);
        $this->assertArrayHasKey('next', $nextPrev);
    }

    /**
     *
     */
    public function test_resolveTranscriptTitle_prefers_the_subsection_title_when_set() {
        $result = HansardSpeechView::resolveTranscriptTitle('Motions', 'Health Funding', '&nbsp;', 'House debates');

        $this->assertTrue($result['hasSubsectionTitle']);
        $this->assertSame('Health Funding', $result['finalTitle']);
        $this->assertSame('Motions', $result['eyebrowSectionTitle']);
    }

    /**
     * An htype-11 row occurred with no preceding htype-10 row - $sectionTitle is
     * still the sentinel even though $hasSubsectionTitle is true. transcript.php
     * treats any non-empty sectionTitle as real and renders a "·" separator before
     * it, so the sentinel itself must never reach there. Sentry finding on #227.
     */
    public function test_resolveTranscriptTitle_blanks_the_eyebrow_section_title_when_only_the_sentinel_is_set() {
        $result = HansardSpeechView::resolveTranscriptTitle('&nbsp;', 'Health Funding', '&nbsp;', 'House debates');

        $this->assertTrue($result['hasSubsectionTitle']);
        $this->assertSame('', $result['eyebrowSectionTitle']);
    }

    /**
     * No htype-11 row occurred, so $subsectionTitle is still the sentinel - falls
     * back to the section title instead.
     */
    public function test_resolveTranscriptTitle_falls_back_to_the_section_title() {
        $result = HansardSpeechView::resolveTranscriptTitle('Motions', '&nbsp;', '&nbsp;', 'House debates');

        $this->assertFalse($result['hasSubsectionTitle']);
        $this->assertSame('Motions', $result['finalTitle']);
    }

    /**
     * Neither an htype-10 nor an htype-11 row occurred - both titles are still the
     * sentinel, so the only thing left to show is the major's own label.
     */
    public function test_resolveTranscriptTitle_falls_back_to_the_major_title_when_neither_is_set() {
        $result = HansardSpeechView::resolveTranscriptTitle('&nbsp;', '&nbsp;', '&nbsp;', 'House debates');

        $this->assertFalse($result['hasSubsectionTitle']);
        $this->assertSame('House debates', $result['finalTitle']);
    }

    /**
     * Two letters (eg "LT" for Lidia Thorpe), matching the mockup's own placeholder
     * avatars - not the single first-letter-of-the-full-name-string this used before,
     * which would also have wrongly included a title ("S" for "Senator ...", not the
     * person's own initials at all).
     */
    public function test_initials_takes_the_first_letter_of_first_and_last_name() {
        $this->assertSame('LT', HansardSpeechView::initials('Lidia', 'Thorpe'));
    }

    /**
     *
     */
    public function test_initials_uppercases_and_handles_a_missing_name_gracefully() {
        $this->assertSame('PA', HansardSpeechView::initials('penny', 'allman-payne'));
        $this->assertSame('P', HansardSpeechView::initials('Penny', ''));
        $this->assertSame('', HansardSpeechView::initials('', ''));
    }

    /**
     * member.first_name/last_name are nullable columns (db/schema.sql) - a real DB
     * row can hand initials() null, not just ''. The params carry no type hints
     * (Sentry finding on #227: a string type hint would fatal with a TypeError
     * instead) - this test verifies that actually holds, not just that the
     * signature allows it.
     */
    public function test_initials_handles_a_null_name_without_a_typeerror() {
        $this->assertSame('T', HansardSpeechView::initials(null, 'Thorpe'));
        $this->assertSame('L', HansardSpeechView::initials('Lidia', null));
        $this->assertSame('', HansardSpeechView::initials(null, null));
    }

    /**
     * The hansard table has no body column of its own (it's assembled from
     * elsewhere - see hansard_gid.php's own $bodies handling), so cleanBody() can't
     * point at one schema line the way initials() can - but it's the same shape of
     * risk (a real value this code can plausibly receive, against a non-nullable
     * param), so this locks in the same defensive treatment.
     */
    public function test_cleanBody_handles_a_null_body_without_a_typeerror() {
        $this->assertSame('', HansardSpeechView::cleanBody(null));
    }

    /**
     * @return array<string, mixed>
     */
    private function speechRow(array $overrides = []): array {
        return array_merge([
            'gid' => '2026-08-18.108.2',
            'htime' => '00:00:00',
            'speaker' => [],
            'body' => '<p>Some text.</p>',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function speaker(array $overrides = []): array {
        return array_merge([
            // Senate - see member.php; 101 is the hansard *major*, a different,
            // unrelated numbering (see $hansardmajors in dbtypes.php).
            'house' => 2,
            'title' => '',
            'first_name' => 'Penny',
            'last_name' => 'Allman-Payne',
            'constituency' => 'Queensland',
            'party' => 'Australian Greens',
            'url' => '/senator/?m=100931',
            'person_id' => 999999999,
            'member_id' => 100931,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function info(array $overrides = []): array {
        return array_merge(['major' => 101], $overrides);
    }

    /**
     * A HansardSpeechView built by setting its public fields directly, bypassing
     * forSpeech()'s row-parsing entirely - buildRoster() only ever reads these
     * already-finished fields, so that's all its own tests need to construct.
     */
    private function speechView(
      string $name,
      string $url,
      string $bodyText,
      ?string $description = null,
      ?string $avatarUrl = null,
      ?string $initials = null,
      ?string $speakerId = null,
    ): HansardSpeechView {
        $view = new HansardSpeechView();
        $view->speakerName = $name;
        $view->speakerInitials = $initials;
        $view->speakerUrl = $url;
        $view->speakerId = $speakerId;
        $view->speakerDescription = $description;
        $view->avatarUrl = $avatarUrl;
        $view->bodyHtml = '<p>' . $bodyText . '</p>';
        return $view;
    }

}
