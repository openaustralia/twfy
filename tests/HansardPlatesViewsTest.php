<?php

/**
 * @file
 * Renders each of the new Plates templates under resources/views/hansard/ (see
 * hansard_gid.php, major 1/101 only) directly through a real League\Plates\Engine,
 * with plain fixture view-model objects - no DB, no $PAGE/$DATA globals. Unlike
 * HansardSpeechViewTest.php (which tests the view-model layer that *builds* these
 * objects), this file tests the templates themselves: every conditional branch each
 * one has (avatar vs initials fallback, a link vs plain text, a direction present vs
 * missing, ...), since that's exactly what "view snippet" coverage means for a
 * template file with no logic of its own beyond echoing already-finished data.
 */

use League\Plates\Engine;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../www/includes/easyparliament/member.php';
require_once __DIR__ . '/../www/includes/easyparliament/HansardSpeechView.php';

if (!defined('TIMEFORMAT')) {
    define('TIMEFORMAT', 'g:i a');
}

/**
 * Same stubs as HansardSpeechViewTest.php, for the same reason (utility.php
 * unconditionally redeclares twfy_debug(), already stubbed by bootstrap.php) -
 * function_exists-guarded so it's harmless if that file's own copies loaded first
 * in the same test run.
 */
if (!function_exists('gid_to_anchor')) {

    function gid_to_anchor($gid) {
        return substr($gid, (strpos($gid, '.') + 1));
    }

}

/**
 * Plates templates under test render real, escaped, hand-built view models - no DB,
 * no page chrome. One shared Engine instance is enough for every test here.
 */
class HansardPlatesViewsTest extends TestCase {

    private Engine $engine;

    /**
     *
     */
    protected function setUp(): void {
        $this->engine = new Engine(__DIR__ . '/../www/resources/views');
    }

    /**
     *
     */
    public function test_about_debates_renders_the_title_and_body() {
        $html = $this->engine->render('hansard/about-debates', [
            'title' => 'What are House debates?',
            'bodyHtml' => '<p>Debates are <strong>important</strong>.</p>',
        ]);

        $this->assertStringContainsString('What are House debates?', $html);
        $this->assertStringContainsString('<p>Debates are <strong>important</strong>.</p>', $html);
    }

    /**
     *
     */
    public function test_procedural_renders_the_id_and_body() {
        $item = $this->proceduralView(['id' => 'g108.5', 'bodyHtml' => '<p>The House divided.</p>']);

        $html = $this->engine->render('hansard/procedural', ['item' => $item]);

        $this->assertStringContainsString('id="g108.5"', $html);
        $this->assertStringContainsString('<p>The House divided.</p>', $html);
    }

    /**
     *
     */
    public function test_procedural_renders_context_and_comment_links_when_present() {
        $item = $this->proceduralView([
            'contextLinkHtml' => '<a href="/x">context</a>',
            'commentTeaserHtml' => '<a href="/y">2 comments</a>',
        ]);

        $html = $this->engine->render('hansard/procedural', ['item' => $item]);

        $this->assertStringContainsString('<a href="/x">context</a>', $html);
        $this->assertStringContainsString('<a href="/y">2 comments</a>', $html);
    }

    /**
     *
     */
    public function test_procedural_omits_the_footer_row_when_both_links_are_empty() {
        $item = $this->proceduralView(['contextLinkHtml' => '', 'commentTeaserHtml' => '']);

        $html = $this->engine->render('hansard/procedural', ['item' => $item]);

        $this->assertStringNotContainsString('mt-2 not-italic', $html);
    }

    /**
     *
     */
    public function test_speech_renders_a_named_speaker_with_an_avatar() {
        $speech = $this->speechView([
            'speakerName' => 'Penny Allman-Payne',
            'speakerDescription' => 'Queensland, Australian Greens',
            'avatarUrl' => '/images/mpsL/100931.jpg',
            'speakerUrl' => '/senator/?m=100931',
        ]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringContainsString('src="/images/mpsL/100931.jpg"', $html);
        $this->assertStringContainsString('alt="Photo of Penny Allman-Payne"', $html);
        $this->assertStringContainsString('href="/senator/?m=100931"', $html);
        $this->assertStringContainsString('Penny Allman-Payne', $html);
        $this->assertStringContainsString('Queensland, Australian Greens', $html);
    }

    /**
     *
     */
    public function test_speech_falls_back_to_initials_when_there_is_no_avatar() {
        $speech = $this->speechView([
            'speakerName' => 'Penny Allman-Payne',
            'speakerInitials' => 'PA',
            'avatarUrl' => null,
        ]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('font-semibold text-lg', $html);
        $this->assertStringContainsString('PA', $html);
    }

    /**
     *
     */
    public function test_speech_renders_the_speaker_name_as_plain_text_when_there_is_no_url() {
        $speech = $this->speechView(['speakerName' => 'The Speaker', 'speakerUrl' => null]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('The Speaker', $html);
    }

    /**
     *
     */
    public function test_speech_omits_the_speaker_block_entirely_when_there_is_no_speaker() {
        $speech = $this->speechView(['speakerName' => null]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringNotContainsString('font-bold text-slate-900', $html);
    }

    /**
     *
     */
    public function test_speech_shows_the_timestamp_only_when_present() {
        $shown = $this->speechView(['timestamp' => '2:26 pm']);
        $hidden = $this->speechView(['timestamp' => null]);

        $shownHtml = $this->engine->render('hansard/speech', ['speech' => $shown]);
        $hiddenHtml = $this->engine->render('hansard/speech', ['speech' => $hidden]);

        $this->assertStringContainsString('2:26 pm', $shownHtml);
        $this->assertStringNotContainsString('uppercase tracking-wide mb-2', $hiddenHtml);
    }

    /**
     *
     */
    public function test_speech_marks_the_current_speaker_with_a_left_border() {
        $speech = $this->speechView(['isCurrentSpeaker' => true]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringContainsString('border-teal-700', $html);
    }

    /**
     *
     */
    public function test_speech_marks_an_interjection_with_amber_styling_and_italic_body() {
        $speech = $this->speechView(['isInterjection' => true]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringContainsString('border-amber-400', $html);
        $this->assertStringContainsString('italic text-slate-500', $html);
    }

    /**
     *
     */
    public function test_speech_explains_an_unidentified_interjection() {
        $speech = $this->speechView(['isInterjection' => true, 'speakerName' => null]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringContainsString("Hansard doesn't record who said this", $html);
    }

    /**
     * Doesn't apply once there's a real, named speaker - the explanation is
     * specifically for the case where Hansard itself never names anyone.
     */
    public function test_speech_omits_the_interjection_explanation_when_the_speaker_is_named() {
        $speech = $this->speechView(['isInterjection' => true, 'speakerName' => 'Larissa Waters']);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringNotContainsString("Hansard doesn't record who said this", $html);
    }

    /**
     *
     */
    public function test_speech_renders_the_source_context_and_comment_links_when_present() {
        $speech = $this->speechView([
            'sourceUrl' => 'https://parlinfo.aph.gov.au/x',
            'sourceLabel' => 'Hansard source',
            'contextLinkHtml' => '<a href="/x">See this speech in context</a>',
            'commentTeaserHtml' => '<a href="/y">2 comments</a>',
        ]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringContainsString('href="https://parlinfo.aph.gov.au/x"', $html);
        $this->assertStringContainsString('Hansard source', $html);
        $this->assertStringContainsString('See this speech in context', $html);
        $this->assertStringContainsString('2 comments', $html);
    }

    /**
     * The old stripe rendering showed this for every speech (hansard_gid.php's
     * non-Plates path) - HansardSpeechView already computed permalinkUrl, but no
     * Plates template read it, so it silently disappeared for these two majors.
     */
    public function test_speech_renders_the_permalink_when_present() {
        $speech = $this->speechView(['permalinkUrl' => '/debate/?id=2026-08-18.108.2']);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringContainsString('href="/debate/?id=2026-08-18.108.2"', $html);
        $this->assertStringContainsString('Link to this', $html);
    }

    /**
     *
     */
    public function test_speech_omits_the_footer_row_when_there_is_nothing_to_show_there() {
        $speech = $this->speechView([
            'sourceUrl' => null,
            'contextLinkHtml' => '',
            'commentTeaserHtml' => '',
            'permalinkUrl' => null,
        ]);

        $html = $this->engine->render('hansard/speech', ['speech' => $speech]);

        $this->assertStringNotContainsString('mt-3 text-sm text-slate-500 space-x-3', $html);
    }

    /**
     *
     */
    public function test_speaker_roster_renders_each_speaker_with_a_photo_link_and_speech_count() {
        $entry = new HansardSpeakerRosterEntry();
        $entry->name = 'Katy Gallagher';
        $entry->initials = 'KG';
        $entry->url = '/senator/?m=100907';
        $entry->avatarUrl = '/images/mpsL/10838.jpg';
        $entry->description = 'ALP, Australian Capital Territory';
        $entry->speechCount = 3;

        $html = $this->engine->render('hansard/speaker-roster', ['speakers' => [$entry]]);

        $this->assertStringContainsString('src="/images/mpsL/10838.jpg"', $html);
        $this->assertStringContainsString('href="/senator/?m=100907"', $html);
        $this->assertStringContainsString('Katy Gallagher', $html);
        $this->assertStringContainsString('ALP, Australian Capital Territory', $html);
        $this->assertStringContainsString('spoke 3 times', $html);
        $this->assertStringContainsString('3x', $html);
    }

    /**
     *
     */
    public function test_speaker_roster_falls_back_to_initials_when_there_is_no_avatar() {
        $entry = new HansardSpeakerRosterEntry();
        $entry->name = 'Katy Gallagher';
        $entry->initials = 'KG';
        $entry->avatarUrl = null;

        $html = $this->engine->render('hansard/speaker-roster', ['speakers' => [$entry]]);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('>KG<', $html);
    }

    /**
     *
     */
    public function test_speaker_roster_renders_a_plain_name_when_there_is_no_url() {
        $entry = new HansardSpeakerRosterEntry();
        $entry->name = 'The Speaker';
        $entry->initials = 'TS';

        $html = $this->engine->render('hansard/speaker-roster', ['speakers' => [$entry]]);

        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('The Speaker', $html);
    }

    /**
     *
     */
    public function test_speaker_roster_omits_the_description_line_when_absent() {
        $entry = new HansardSpeakerRosterEntry();
        $entry->name = 'The Speaker';
        $entry->initials = 'TS';
        $entry->description = null;

        $html = $this->engine->render('hansard/speaker-roster', ['speakers' => [$entry]]);

        $this->assertStringNotContainsString('text-sm text-slate-500 truncate', $html);
    }

    /**
     *
     */
    public function test_speaker_chips_renders_an_avatar_when_present() {
        $speaker = $this->speakerRosterEntry([
            'name' => 'Penny Allman-Payne',
            'avatarUrl' => '/images/mpsL/100931.jpg',
        ]);

        $html = $this->engine->render('hansard/speaker-chips', ['speakers' => [$speaker]]);

        $this->assertStringContainsString('src="/images/mpsL/100931.jpg"', $html);
        $this->assertStringContainsString('alt=""', $html);
        $this->assertStringContainsString('Penny Allman-Payne', $html);
    }

    /**
     *
     */
    public function test_speaker_chips_falls_back_to_initials_when_there_is_no_avatar() {
        $speaker = $this->speakerRosterEntry(['name' => 'Penny Allman-Payne', 'initials' => 'PA', 'avatarUrl' => null]);

        $html = $this->engine->render('hansard/speaker-chips', ['speakers' => [$speaker]]);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('PA', $html);
    }

    /**
     *
     */
    public function test_speaker_chips_shows_the_description_as_a_title_tooltip() {
        // Already-escaped, same contract HansardSpeechView::speakerDescription()
        // guarantees (htmlentities() over the whole assembled string) - the template
        // must not re-escape it, or a "&" in a real party/electorate name would come
        // out as the literal text "&amp;" in the tooltip, not a "&".
        $speaker = $this->speakerRosterEntry([
            'name' => 'Katy Gallagher',
            'description' => 'ALP, Australian Capital Territory &amp; &quot;Territories&quot; Minister',
        ]);

        $html = $this->engine->render('hansard/speaker-chips', ['speakers' => [$speaker]]);

        $this->assertStringContainsString('title="ALP, Australian Capital Territory &amp; &quot;Territories&quot; Minister"', $html);
    }

    /**
     *
     */
    public function test_speaker_chips_omits_the_title_attribute_when_there_is_no_description() {
        $speaker = $this->speakerRosterEntry(['name' => 'Katy Gallagher', 'description' => null]);

        $html = $this->engine->render('hansard/speaker-chips', ['speakers' => [$speaker]]);

        $this->assertStringNotContainsString('title=', $html);
    }

    /**
     *
     */
    public function test_speaker_chips_renders_every_speaker_in_order() {
        $html = $this->engine->render('hansard/speaker-chips', [
            'speakers' => [
                $this->speakerRosterEntry(['name' => 'Larissa Waters']),
                $this->speakerRosterEntry(['name' => 'Katy Gallagher']),
            ],
        ]);

        $firstPos = strpos($html, 'Larissa Waters');
        $secondPos = strpos($html, 'Katy Gallagher');
        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertLessThan($secondPos, $firstPos);
    }

    /**
     * Only latest_activity_items() (www/docs/index.php) ever sets firstSpeechUrl -
     * section-index.php's own chips (never set it) stay plain text, since its own
     * linked card variant already wraps the whole chip row in its own <a>, and a
     * nested <a> isn't valid HTML.
     */
    public function test_speaker_chips_links_the_chip_when_firstSpeechUrl_is_set() {
        $speaker = $this->speakerRosterEntry(['name' => 'Zali Steggall', 'firstSpeechUrl' => '/debates/?id=2026-08-20.17.1#g17.2']);

        $html = $this->engine->render('hansard/speaker-chips', ['speakers' => [$speaker]]);

        $this->assertStringContainsString('href="/debates/?id=2026-08-20.17.1#g17.2"', $html);
    }

    /**
     * A linked chip's own title="" would sit closer in the DOM than the <li>'s and
     * win the hover tooltip, hiding the party/electorate description entirely
     * whenever a chip happens to be a link (Copilot review finding on #228) - the
     * link's purpose has to reach screen readers some other way instead.
     */
    public function test_speaker_chips_link_uses_aria_label_not_title_so_the_description_tooltip_still_shows() {
        $speaker = $this->speakerRosterEntry([
            'name' => 'Zali Steggall',
            'description' => 'Warringah, Independent',
            'firstSpeechUrl' => '/debates/?id=2026-08-20.17.1#g17.2',
        ]);

        $html = $this->engine->render('hansard/speaker-chips', ['speakers' => [$speaker]]);

        $this->assertStringContainsString('<li class="min-w-0" title="Warringah, Independent">', $html);
        $this->assertStringContainsString('aria-label="See Zali Steggall\'s first speech here"', $html);
        $this->assertStringNotContainsString('title="See', $html);
    }

    /**
     *
     */
    public function test_speaker_chips_renders_a_plain_chip_when_there_is_no_firstSpeechUrl() {
        $speaker = $this->speakerRosterEntry(['name' => 'Zali Steggall', 'firstSpeechUrl' => null]);

        $html = $this->engine->render('hansard/speaker-chips', ['speakers' => [$speaker]]);

        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('Zali Steggall', $html);
    }

    /**
     *
     */
    public function test_speaker_chips_shows_how_many_further_speakers_did_not_fit() {
        $html = $this->engine->render('hansard/speaker-chips', [
            'speakers' => [$this->speakerRosterEntry()],
            'moreCount' => 4,
        ]);

        $this->assertStringContainsString('+4 more', $html);
    }

    /**
     * moreCount is optional - only latest_activity_items() passes it.
     * section-index.php's own call site doesn't, so it must not warn/error.
     */
    public function test_speaker_chips_omits_the_more_chip_when_moreCount_is_not_passed() {
        $html = $this->engine->render('hansard/speaker-chips', ['speakers' => [$this->speakerRosterEntry()]]);

        $this->assertStringNotContainsString('more', $html);
    }

    /**
     *
     */
    public function test_pagination_renders_prev_and_next_cards_with_labels_and_titles() {
        $html = $this->engine->render('hansard/pagination', [
            'nextPrev' => [
                'prev' => ['label' => 'Previous debate', 'url' => '/debates/?id=a', 'title' => 'Motions'],
                'next' => ['label' => 'Next debate', 'url' => '/debates/?id=b', 'title' => 'Bills'],
            ],
            'edge' => 'top',
        ]);

        $this->assertStringContainsString('href="/debates/?id=a"', $html);
        $this->assertStringContainsString('Previous debate', $html);
        $this->assertStringContainsString('Motions', $html);
        $this->assertStringContainsString('href="/debates/?id=b"', $html);
        $this->assertStringContainsString('Next debate', $html);
        $this->assertStringContainsString('Bills', $html);
    }

    /**
     *
     */
    public function test_pagination_renders_the_up_link_when_it_has_a_url() {
        $html = $this->engine->render('hansard/pagination', [
            'nextPrev' => ['up' => ['label' => 'All House debates on this day', 'url' => '/debates/?d=x', 'title' => '']],
            'edge' => 'top',
        ]);

        $this->assertStringContainsString('href="/debates/?d=x"', $html);
        $this->assertStringContainsString('All House debates on this day', $html);
    }

    /**
     * _get_nextprev_items() sometimes returns a direction with a label but no url -
     * a plain-text fallback, not a link.
     */
    public function test_pagination_falls_back_to_plain_text_when_a_direction_has_no_url() {
        $html = $this->engine->render('hansard/pagination', [
            'nextPrev' => ['prev' => ['label' => 'No earlier item', 'url' => null, 'title' => '']],
            'edge' => 'top',
        ]);

        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('No earlier item', $html);
    }

    /**
     * Same fallback as 'prev' above, for the other two directions - 'next' is its
     * own mirrored branch in the template (sm:text-right, arrow after the label
     * instead of before), and 'up' is a plain <span>, not a card at all.
     */
    public function test_pagination_falls_back_to_plain_text_for_a_linkless_next_direction() {
        $html = $this->engine->render('hansard/pagination', [
            'nextPrev' => ['next' => ['label' => 'No later item', 'url' => null, 'title' => '']],
            'edge' => 'top',
        ]);

        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('No later item', $html);
    }

    /**
     *
     */
    public function test_pagination_falls_back_to_plain_text_for_a_linkless_up_direction() {
        $html = $this->engine->render('hansard/pagination', [
            'nextPrev' => ['up' => ['label' => 'See the whole debate', 'url' => null, 'title' => '']],
            'edge' => 'top',
        ]);

        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('See the whole debate', $html);
    }

    /**
     *
     */
    public function test_pagination_omits_a_direction_that_is_missing_entirely() {
        $html = $this->engine->render('hansard/pagination', [
            'nextPrev' => ['next' => ['label' => 'Next debate', 'url' => '/debates/?id=b', 'title' => 'Bills']],
            'edge' => 'top',
        ]);

        $this->assertStringNotContainsString('Previous debate', $html);
    }

    /**
     * Distinct labels top vs bottom (axe's landmark-unique rule - two <nav> regions
     * on one page can't share an accessible name).
     */
    public function test_pagination_uses_a_distinct_aria_label_at_the_bottom_edge() {
        $nextPrev = ['up' => ['label' => 'All House debates on this day', 'url' => '/debates/?d=x', 'title' => '']];

        $top = $this->engine->render('hansard/pagination', ['nextPrev' => $nextPrev, 'edge' => 'top']);
        $bottom = $this->engine->render('hansard/pagination', ['nextPrev' => $nextPrev, 'edge' => 'bottom']);

        $this->assertStringContainsString('aria-label="Debate navigation"', $top);
        $this->assertStringContainsString('aria-label="Debate navigation, end of transcript"', $bottom);
    }

    /**
     *
     */
    public function test_transcript_renders_the_chamber_eyebrow_title_and_date() {
        $html = $this->transcriptHtml([
            'chamberLabel' => 'Senate debates',
            'sectionTitle' => 'Documents',
            'subsectionTitle' => 'AdStop',
            'date' => 'Thursday, 20 August 2026',
            'dateUrl' => '/senate/?d=2026-08-20',
            'chamber' => 'Senate',
        ]);

        $this->assertStringContainsString('Senate debates', $html);
        $this->assertStringContainsString('Documents', $html);
        $this->assertStringContainsString('AdStop', $html);
        $this->assertStringContainsString('href="/senate/?d=2026-08-20"', $html);
        $this->assertStringContainsString('Thursday, 20 August 2026', $html);
        $this->assertStringContainsString('Senate', $html);
    }

    /**
     *
     */
    public function test_transcript_renders_the_date_as_plain_text_when_there_is_no_dateUrl() {
        $html = $this->transcriptHtml(['dateUrl' => null, 'date' => 'Thursday, 20 August 2026']);

        $this->assertStringNotContainsString('title="See all debates on this date"', $html);
        $this->assertStringContainsString('Thursday, 20 August 2026', $html);
    }

    /**
     *
     */
    public function test_transcript_dispatches_speech_and_procedural_items_in_page_order() {
        $html = $this->transcriptHtml([
            'items' => [
                $this->speechView(['speakerName' => 'Larissa Waters', 'bodyHtml' => '<p>First.</p>']),
                $this->proceduralView(['bodyHtml' => '<p>The Senate divided.</p>']),
            ],
        ]);

        $firstPos = strpos($html, 'Larissa Waters');
        $secondPos = strpos($html, 'The Senate divided.');
        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertLessThan($secondPos, $firstPos);
    }

    /**
     *
     */
    public function test_transcript_renders_pagination_top_and_bottom_when_nextPrev_is_present() {
        $html = $this->transcriptHtml([
            'nextPrev' => ['next' => ['label' => 'Next debate', 'url' => '/debates/?id=b', 'title' => 'Bills']],
        ]);

        $this->assertSame(2, substr_count($html, 'aria-label="Debate navigation'));
    }

    /**
     *
     */
    public function test_transcript_omits_pagination_entirely_when_nextPrev_is_empty() {
        $html = $this->transcriptHtml(['nextPrev' => []]);

        $this->assertStringNotContainsString('aria-label="Debate navigation', $html);
    }

    /**
     *
     */
    public function test_transcript_renders_the_sidebar_when_about_or_roster_content_is_present() {
        $roster = new HansardSpeakerRosterEntry();
        $roster->name = 'Katy Gallagher';
        $roster->initials = 'KG';

        $html = $this->transcriptHtml([
            'aboutTitle' => 'What are Senate debates?',
            'aboutBodyHtml' => '<p>Debates are important.</p>',
            'speakers' => [$roster],
        ]);

        $this->assertStringContainsString('What are Senate debates?', $html);
        $this->assertStringContainsString('Katy Gallagher', $html);
    }

    /**
     *
     */
    public function test_transcript_omits_the_sidebar_entirely_when_there_is_nothing_for_it() {
        $html = $this->transcriptHtml(['aboutBodyHtml' => '', 'speakers' => []]);

        $this->assertStringNotContainsString('lg:col-span-1', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function transcriptHtml(array $overrides = []): string {
        return $this->engine->render('hansard/transcript', array_merge([
            'items' => [],
            'speakers' => [],
            'chamberLabel' => 'Senate debates',
            'sectionTitle' => 'Documents',
            'subsectionTitle' => 'AdStop',
            'date' => 'Thursday, 20 August 2026',
            'dateUrl' => '/senate/?d=2026-08-20',
            'chamber' => 'Senate',
            'aboutTitle' => '',
            'aboutBodyHtml' => '',
            'nextPrev' => [],
        ], $overrides));
    }

    /**
     * A HansardSpeechView built by setting its public fields directly - this file
     * only exercises the template layer, not forSpeech()'s own row-parsing (see
     * HansardSpeechViewTest.php for that).
     *
     * @return array<string, mixed>
     */
    private function speechView(array $overrides = []): HansardSpeechView {
        $view = new HansardSpeechView();
        $view->id = 'g108.2';
        $view->timestamp = null;
        $view->speakerName = null;
        $view->speakerInitials = null;
        $view->speakerUrl = null;
        $view->speakerDescription = null;
        $view->avatarUrl = null;
        $view->isCurrentSpeaker = false;
        $view->isInterjection = false;
        $view->bodyHtml = '<p>Some text.</p>';
        $view->sourceUrl = null;
        $view->sourceLabel = null;
        $view->contextLinkHtml = '';
        $view->commentTeaserHtml = '';
        foreach ($overrides as $key => $value) {
            $view->$key = $value;
        }
        return $view;
    }

    /**
     * @return array<string, mixed>
     */
    private function proceduralView(array $overrides = []): HansardProceduralView {
        $view = new HansardProceduralView();
        $view->id = 'g108.1';
        $view->bodyHtml = '<p>The House divided.</p>';
        $view->contextLinkHtml = '';
        $view->commentTeaserHtml = '';
        foreach ($overrides as $key => $value) {
            $view->$key = $value;
        }
        return $view;
    }

    /**
     *
     */
    private function speakerRosterEntry(array $overrides = []): HansardSpeakerRosterEntry {
        $entry = new HansardSpeakerRosterEntry();
        $entry->name = 'Larissa Waters';
        $entry->initials = 'LW';
        $entry->url = null;
        $entry->avatarUrl = null;
        $entry->description = null;
        $entry->firstSpeechUrl = null;
        foreach ($overrides as $key => $value) {
            $entry->$key = $value;
        }
        return $entry;
    }

}
