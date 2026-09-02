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

/**
 * hansard_gid.php defines these itself (see its context_link()/generate_commentteaser())
 * for the stripe-rendering path this file's classes don't use - they're plain HTML
 * strings built from DB-backed comment data, not something worth standing up a
 * database for here. Stubbed the same way tests/bootstrap.php already stubs
 * twfy_debug() etc: real enough to satisfy forSpeech()/forProcedural()'s calls,
 * with output distinct enough (the gid) to assert the right $row reached them.
 */
if (!function_exists('context_link')) {

    function context_link($row) {
        return '<context-link:' . $row['gid'] . '>';
    }

}

if (!function_exists('generate_commentteaser')) {

    function generate_commentteaser($row, $major) {
        return '<comment-teaser:' . $row['gid'] . ':' . $major . '>';
    }

}

/**
 * Real trim_characters() (www/includes/utility.php) strips tags, trims to a word
 * boundary, and - the one behaviour HansardSectionIndexItem::fromSubrow()'s own test
 * cares about - leaves HTML entities alone (eg "&#8212;" stays "&#8212;", it isn't
 * decoded to an em-dash). This stub keeps just that; not the word-boundary trimming.
 */
if (!function_exists('trim_characters')) {

    function trim_characters($text, $start, $length) {
        $text = strip_tags($text);
        if (strlen($text) > $length) {
            $text = substr($text, 0, $length) . '...';
        }
        return $text;
    }

}

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
     * member.party stores the raw code for a chair role (SPK/CWM/DCWM/PRES/DPRES -
     * see dbtypes.php's $parties), not a display name - a Deputy Speaker still
     * has a constituency on file, but showing it here would be misleading, since
     * they're not speaking as their electorate's member while in the chair.
     */
    public function test_forSpeech_omits_the_constituency_for_a_chair_role() {
        $row = $this->speechRow([
            'speaker' => $this->speaker(['party' => 'CWM', 'constituency' => 'Queensland']),
        ]);

        $view = HansardSpeechView::forSpeech($row, $this->info(), true);

        $this->assertSame('CWM', $view->speakerDescription);
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
     * 'up's own URL needs overwriting along with its label, not just used as a
     * fallback: HANSARDLIST::_get_nextprev_items()'s ordinary-item branch points it
     * at the parent subsection/section page, a different destination to the
     * day-listing page "All ... on this day" promises. $dateUrl is that day-listing
     * page - buildNextPrev() must use it, not whatever URL the row itself carried.
     */
    public function test_buildNextPrev_builds_all_three_directions_and_relabels_and_relinks_up() {
        $nextprevdata = [
            'prev' => ['body' => 'Earlier Debate Title', 'url' => '/debates/?id=a', 'title' => 'Earlier Debate Title'],
            // 'up's own label/url ("All Senate debates on 18 Aug 2026", or sometimes
            // "See the whole debate"/the parent section's page - see
            // _get_nextprev_items()) are always replaced, not used as a fallback, so
            // what they say here shouldn't matter.
            'up' => ['body' => 'See the whole debate', 'url' => '/debates/?id=parent-section'],
            'next' => ['body' => 'Later Debate Title', 'url' => '/debates/?id=b', 'title' => 'Later Debate Title'],
        ];

        $nextPrev = HansardSpeechView::buildNextPrev($nextprevdata, 'House debates', '/debates/?d=2026-08-20');

        $this->assertSame(['label' => 'Earlier Debate Title', 'url' => '/debates/?id=a', 'title' => 'Earlier Debate Title'], $nextPrev['prev']);
        $this->assertSame('All House debates on this day', $nextPrev['up']['label']);
        $this->assertSame('/debates/?d=2026-08-20', $nextPrev['up']['url']);
        $this->assertSame(['label' => 'Later Debate Title', 'url' => '/debates/?id=b', 'title' => 'Later Debate Title'], $nextPrev['next']);
    }

    /**
     *
     */
    public function test_buildNextPrev_omits_a_direction_the_row_does_not_have() {
        // Eg the very first debate ever recorded has no 'prev'.
        $nextprevdata = [
            'next' => ['body' => 'Later Debate Title', 'url' => '/debates/?id=b'],
        ];

        $nextPrev = HansardSpeechView::buildNextPrev($nextprevdata, 'House debates', '/debates/?d=2026-08-20');

        $this->assertArrayNotHasKey('prev', $nextPrev);
        $this->assertArrayNotHasKey('up', $nextPrev);
        $this->assertArrayHasKey('next', $nextPrev);
    }

    /**
     * _get_nextprev_items() sometimes returns a direction with a label but no 'url' -
     * a plain-text fallback, not a link. buildNextPrev() has to preserve that rather
     * than pretending every present direction is clickable.
     */
    public function test_buildNextPrev_leaves_the_url_null_when_the_row_has_none() {
        $nextprevdata = [
            'prev' => ['body' => 'No earlier item'],
        ];

        $nextPrev = HansardSpeechView::buildNextPrev($nextprevdata, 'House debates', '/debates/?d=2026-08-20');

        $this->assertSame('No earlier item', $nextPrev['prev']['label']);
        $this->assertNull($nextPrev['prev']['url']);
    }

    /**
     *
     */
    public function test_aboutSection_matches_a_known_section_title_regardless_of_chamber() {
        $reps = HansardSpeechView::aboutSection('Adjournment', 1, 'House debates');
        $senate = HansardSpeechView::aboutSection('Adjournment', 101, 'Senate debates');

        $this->assertSame('What is the Adjournment?', $reps['title']);
        $this->assertStringContainsString('adjourns', $reps['bodyHtml']);
        // Same explanation either way - the Adjournment isn't chamber-specific.
        $this->assertSame($reps, $senate);
    }

    /**
     *
     */
    public function test_aboutSection_matches_each_known_section_title() {
        $this->assertSame('What is a Bill?', HansardSpeechView::aboutSection('Bills', 1, 'House debates')['title']);
        $this->assertSame('What are Committees?', HansardSpeechView::aboutSection('Committees', 1, 'House debates')['title']);
        $this->assertSame(
            'What is a Matter of Public Importance?',
            HansardSpeechView::aboutSection('Matters of Public Importance', 1, 'House debates')['title']
        );
        // Lower-case "without" - matches the section title as this fork's data
        // actually has it, not the more conventional-looking capitalised form.
        $this->assertSame(
            'What is Question Time?',
            HansardSpeechView::aboutSection('Questions without Notice', 1, 'House debates')['title']
        );
    }

    /**
     *
     */
    public function test_aboutSection_falls_back_to_the_chamber_level_explanation_for_an_unknown_section_title() {
        $reps = HansardSpeechView::aboutSection('Some New Section Type', 1, 'House debates');
        $senate = HansardSpeechView::aboutSection('Some New Section Type', 101, 'Senate debates');

        $this->assertSame('What are House debates?', $reps['title']);
        $this->assertStringContainsString('House of Representatives', $reps['bodyHtml']);
        $this->assertSame('What are Senate debates?', $senate['title']);
        $this->assertStringContainsString('Senate', $senate['bodyHtml']);
    }

    /**
     * Neither a known section title nor one of the two known majors - the
     * generic-est fallback, built from whatever $chamberTitle it was given (or
     * "debates" if not even that).
     */
    public function test_aboutSection_falls_back_further_still_for_an_unrecognised_major() {
        $named = HansardSpeechView::aboutSection('Some New Section Type', 3, 'Some Other Chamber');
        $unnamed = HansardSpeechView::aboutSection('Some New Section Type', 3, null);

        $this->assertSame('What are Some Other Chamber?', $named['title']);
        $this->assertSame('', $named['bodyHtml']);
        $this->assertSame('What are debates?', $unnamed['title']);
    }

    /**
     *
     */
    public function test_fromSubrow_a_debate_topic_with_one_speech_sets_url_and_a_singular_count_label() {
        $row = $this->subrow(['contentcount' => 1]);

        $item = HansardSectionIndexItem::fromSubrow($row, $this->hansardmajors());

        $this->assertSame('/debates/?id=2026-08-20.165.2', $item->url);
        $this->assertSame('1 speech', $item->countLabel);
    }

    /**
     *
     */
    public function test_fromSubrow_a_debate_topic_with_several_speeches_pluralizes_the_count_label() {
        $row = $this->subrow(['contentcount' => 3]);

        $item = HansardSectionIndexItem::fromSubrow($row, $this->hansardmajors());

        $this->assertSame('3 speeches', $item->countLabel);
    }

    /**
     * A Wrans/WMS-style major's htype-11 subrow counts as "has content" even though
     * contentcount is 0 - unlike a debate, its content lives directly under the
     * subsection with no separate speech count worth stating (see fromSubrow()'s own
     * comment: "All Wrans have 2 speeches, all WMS have 1 - no need to say so").
     */
    public function test_fromSubrow_treats_an_other_type_majors_subsection_as_having_content_with_no_speech_count() {
        $row = $this->subrow(['contentcount' => 0, 'major' => 3]);

        $item = HansardSectionIndexItem::fromSubrow($row, $this->hansardmajors());

        $this->assertSame('/debates/?id=2026-08-20.165.2', $item->url);
        $this->assertNull($item->countLabel);
    }

    /**
     *
     */
    public function test_fromSubrow_with_no_content_leaves_the_url_and_count_label_null() {
        $row = $this->subrow(['contentcount' => 0]);

        $item = HansardSectionIndexItem::fromSubrow($row, $this->hansardmajors());

        $this->assertNull($item->url);
        $this->assertNull($item->countLabel);
    }

    /**
     *
     */
    public function test_fromSubrow_adds_the_comment_count_onto_the_count_label() {
        $row = $this->subrow(['contentcount' => 1, 'totalcomments' => 2]);

        $item = HansardSectionIndexItem::fromSubrow($row, $this->hansardmajors());

        $this->assertSame('1 speech, 2 comments', $item->countLabel);
    }

    /**
     * trim_characters() (see this file's own stub above) strips tags but leaves HTML
     * entities alone - fromSubrow() has to pass that straight through as
     * already-safe HTML, not re-escape it (see section-index.php's own comment on
     * this - it was a real, visible double-escaping bug during development).
     */
    public function test_fromSubrow_trims_the_excerpt_and_keeps_html_entities_intact() {
        $row = $this->subrow(['excerpt' => '<p>Coal &#8212; and gas.</p>']);

        $item = HansardSectionIndexItem::fromSubrow($row, $this->hansardmajors());

        $this->assertSame('Coal &#8212; and gas.', $item->excerptHtml);
    }

    /**
     *
     */
    public function test_fromSubrow_builds_a_speaker_chip_for_each_speaker_in_speaking_order() {
        $row = $this->subrow([
            'speakers' => [
                $this->speaker(['first_name' => 'Zali', 'last_name' => 'Steggall', 'url' => '/mp/?m=1']),
                $this->speaker(['first_name' => 'Alicia', 'last_name' => 'Payne', 'url' => '/mp/?m=2']),
            ],
        ]);

        $item = HansardSectionIndexItem::fromSubrow($row, $this->hansardmajors());

        $this->assertCount(2, $item->speakers);
        $this->assertSame('Zali Steggall', $item->speakers[0]->name);
        $this->assertSame('Alicia Payne', $item->speakers[1]->name);
    }

    /**
     *
     */
    public function test_fromSubrow_leaves_speakers_empty_when_the_row_has_none() {
        $item = HansardSectionIndexItem::fromSubrow($this->subrow([]), $this->hansardmajors());

        $this->assertSame([], $item->speakers);
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
     * One entry from $data['subrows'] (see hansardlist.php's _get_hansard_data(),
     * htype-11 branch) - a single topic within a section-index page, eg one MP's
     * Adjournment topic.
     *
     * @return array<string, mixed>
     */
    private function subrow(array $overrides = []): array {
        return array_merge([
            'htype' => '11',
            'major' => 1,
            'body' => 'Climate Change',
            'listurl' => '/debates/?id=2026-08-20.165.2',
            'contentcount' => 1,
            'totalcomments' => 0,
        ], $overrides);
    }

    /**
     * A minimal stand-in for the global $hansardmajors config array (dbtypes.php) -
     * just the 'type' field fromSubrow() actually reads, for the two majors these
     * tests use: 1 (House debates) and 3 (Written Answers/Wrans).
     *
     * @return array<int, array<string, string>>
     */
    private function hansardmajors(): array {
        return [
            1 => ['type' => 'debate'],
            3 => ['type' => 'other'],
        ];
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
