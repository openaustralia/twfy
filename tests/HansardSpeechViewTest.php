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
        // A literal em-dash, not the &#8212; entity: the regex matches the raw body
        // text as stored, before any entity-encoding.
        $row = $this->speechRow(['body' => '<p>Opposition senators interjecting—</p>']);

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
    ): HansardSpeechView {
        $view = new HansardSpeechView();
        $view->speakerName = $name;
        $view->speakerInitials = $initials;
        $view->speakerUrl = $url;
        $view->speakerDescription = $description;
        $view->avatarUrl = $avatarUrl;
        $view->bodyHtml = '<p>' . $bodyText . '</p>';
        return $view;
    }

}
