<?php

/**
 * @file
 * Renders the three Plates templates PR #231 added under
 * resources/views/hansard/ (hansard_gid.php's $data['subrows'] handling - a
 * section-index page, eg an Adjournment debate's own gid page where each MP's
 * topic links out to its own already-Plates-rendered transcript page) directly
 * through a real League\Plates\Engine, with plain fixture view-model objects -
 * no DB, no $PAGE/$DATA globals. Same approach as HansardPlatesViewsTest.php:
 * this tests the templates themselves, not the view-model layer that builds
 * their input (HansardSectionIndexItem::fromSubrow(), covered by
 * HansardSpeechViewTest.php).
 */

use League\Plates\Engine;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../www/includes/easyparliament/member.php';
require_once __DIR__ . '/../www/includes/easyparliament/HansardSpeechView.php';

/**
 *
 */
class SectionIndexPlatesViewsTest extends TestCase {

    private Engine $engine;

    /**
     *
     */
    protected function setUp(): void {
        $this->engine = new Engine(__DIR__ . '/../www/resources/views');
    }

    // -- hansard/section-index-page -----------------------------------------

    /**
     *
     */
    public function test_section_index_page_renders_the_section_title() {
        $html = $this->sectionIndexPageHtml(['sectionTitle' => 'Adjournment']);

        $this->assertStringContainsString('Adjournment', $html);
    }

    /**
     *
     */
    public function test_section_index_page_renders_the_chamber_label_when_present() {
        $html = $this->sectionIndexPageHtml(['chamberLabel' => 'House of Representatives']);

        $this->assertStringContainsString('House of Representatives', $html);
    }

    /**
     *
     */
    public function test_section_index_page_omits_the_chamber_label_when_absent() {
        $html = $this->sectionIndexPageHtml(['chamberLabel' => null]);

        $this->assertStringNotContainsString('mt-1 text-sm font-semibold uppercase tracking-wide text-teal-700', $html);
    }

    /**
     *
     */
    public function test_section_index_page_links_the_date_when_a_dateUrl_is_present() {
        $html = $this->sectionIndexPageHtml([
            'date' => 'Thursday, 20 August 2026',
            'dateUrl' => '/reps/?d=2026-08-20',
        ]);

        $this->assertStringContainsString('href="/reps/?d=2026-08-20"', $html);
        $this->assertStringContainsString('title="See all debates on this date"', $html);
        $this->assertStringContainsString('Thursday, 20 August 2026', $html);
    }

    /**
     *
     */
    public function test_section_index_page_renders_the_date_as_plain_text_when_there_is_no_dateUrl() {
        $html = $this->sectionIndexPageHtml(['date' => 'Thursday, 20 August 2026', 'dateUrl' => null]);

        $this->assertStringNotContainsString('title="See all debates on this date"', $html);
        $this->assertStringContainsString('Thursday, 20 August 2026', $html);
    }

    /**
     *
     */
    public function test_section_index_page_renders_the_chamber_when_present() {
        $html = $this->sectionIndexPageHtml(['chamber' => 'House of Representatives']);

        $this->assertStringContainsString('House of Representatives', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    /**
     *
     */
    public function test_section_index_page_omits_the_chamber_row_when_absent() {
        $html = $this->sectionIndexPageHtml(['chamber' => null]);

        $this->assertStringNotContainsString('building-library', $html);
        $this->assertStringNotContainsString('&middot;', $html);
    }

    /**
     *
     */
    public function test_section_index_page_renders_pagination_top_and_bottom_when_nextPrev_is_present() {
        $html = $this->sectionIndexPageHtml([
            'nextPrev' => ['next' => ['label' => 'Next debate', 'url' => '/debates/?id=b', 'title' => 'Bills']],
        ]);

        $this->assertSame(2, substr_count($html, 'aria-label="Debate navigation'));
    }

    /**
     *
     */
    public function test_section_index_page_omits_pagination_entirely_when_nextPrev_is_empty() {
        $html = $this->sectionIndexPageHtml(['nextPrev' => []]);

        $this->assertStringNotContainsString('aria-label="Debate navigation', $html);
    }

    /**
     *
     */
    public function test_section_index_page_renders_the_sidebar_when_aboutBodyHtml_is_present() {
        $html = $this->sectionIndexPageHtml([
            'aboutTitle' => 'What is the Adjournment?',
            'aboutBodyHtml' => '<p>The Adjournment is important.</p>',
        ]);

        $this->assertStringContainsString('What is the Adjournment?', $html);
        $this->assertStringContainsString('The Adjournment is important.', $html);
    }

    /**
     *
     */
    public function test_section_index_page_omits_the_sidebar_entirely_when_aboutBodyHtml_is_absent() {
        $html = $this->sectionIndexPageHtml(['aboutBodyHtml' => '']);

        $this->assertStringNotContainsString('lg:col-span-1', $html);
    }

    /**
     *
     */
    public function test_section_index_page_dispatches_items_through_section_index() {
        $html = $this->sectionIndexPageHtml([
            'items' => [$this->sectionIndexItem(['titleHtml' => 'Climate Change'])],
        ]);

        $this->assertStringContainsString('Climate Change', $html);
    }

    // -- hansard/section-index ------------------------------------------------

    /**
     *
     */
    public function test_section_index_renders_a_divider_between_items_but_not_before_the_first() {
        $html = $this->engine->render('hansard/section-index', [
            'items' => [
                $this->sectionIndexItem(['titleHtml' => 'First topic']),
                $this->sectionIndexItem(['titleHtml' => 'Second topic']),
            ],
        ]);

        $this->assertSame(1, substr_count($html, '<hr class="border-0 border-t border-slate-200">'));
    }

    /**
     *
     */
    public function test_section_index_links_the_card_when_the_item_has_a_url() {
        $item = $this->sectionIndexItem(['titleHtml' => 'Climate Change', 'url' => '/debates/?id=g108.2']);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringContainsString('href="/debates/?id=g108.2"', $html);
        $this->assertStringContainsString('Climate Change', $html);
        // The hover-only chevron affordance only exists in the linked variant.
        $this->assertStringContainsString('m8.25 4.5 7.5 7.5-7.5 7.5', $html);
    }

    /**
     *
     */
    public function test_section_index_renders_a_plain_card_when_the_item_has_no_url() {
        $item = $this->sectionIndexItem(['titleHtml' => 'Climate Change', 'url' => null]);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringNotContainsString('m8.25 4.5 7.5 7.5-7.5 7.5', $html);
        $this->assertStringContainsString('Climate Change', $html);
    }

    /**
     *
     */
    public function test_section_index_renders_the_count_label_when_present() {
        $item = $this->sectionIndexItem(['countLabel' => '3']);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringContainsString('💬 3</span>', $html);
    }

    /**
     *
     */
    public function test_section_index_omits_the_count_label_when_absent() {
        $item = $this->sectionIndexItem(['countLabel' => null]);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringNotContainsString('💬', $html);
    }

    /**
     *
     */
    public function test_section_index_cites_the_first_speaker_under_the_excerpt() {
        $item = $this->sectionIndexItem([
            'excerptHtml' => 'This is important &#8212; very.',
            'speakers' => [$this->speakerRosterEntry(['name' => 'Larissa Waters'])],
        ]);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringContainsString('This is important &#8212; very.', $html);
        $this->assertStringContainsString('<cite', $html);
        $this->assertStringContainsString('Larissa Waters', $html);
    }

    /**
     *
     */
    public function test_section_index_shows_the_excerpt_without_a_citation_when_there_are_no_speakers() {
        $item = $this->sectionIndexItem(['excerptHtml' => 'Some excerpt text.', 'speakers' => []]);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringContainsString('Some excerpt text.', $html);
        $this->assertStringNotContainsString('<cite', $html);
    }

    /**
     *
     */
    public function test_section_index_shows_the_who_spoke_chips_when_there_is_no_excerpt() {
        $item = $this->sectionIndexItem([
            'excerptHtml' => null,
            'speakers' => [$this->speakerRosterEntry(['name' => 'Katy Gallagher'])],
        ]);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringContainsString('Spoke on this topic', $html);
        $this->assertStringContainsString('Katy Gallagher', $html);
        // No excerpt shown above it, so the "Spoke on this topic" label doesn't
        // need the mt-3 gap.
        $this->assertStringContainsString('" text-xs font-medium uppercase tracking-wide text-slate-500">Spoke on this topic', $html);
    }

    /**
     *
     */
    public function test_section_index_adds_a_top_margin_to_the_who_spoke_label_when_an_excerpt_is_also_shown() {
        $item = $this->sectionIndexItem([
            'excerptHtml' => 'Some excerpt text.',
            'speakers' => [$this->speakerRosterEntry(['name' => 'Katy Gallagher'])],
        ]);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringContainsString('"mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Spoke on this topic', $html);
    }

    /**
     * Same excerpt+citation+chips block as the linked variant above, but the
     * unlinked (<div>, no $item->url) branch has its own copy of the markup -
     * both need covering separately.
     */
    public function test_section_index_shows_the_excerpt_and_chips_in_the_unlinked_variant_too() {
        $item = $this->sectionIndexItem([
            'url' => null,
            'excerptHtml' => 'This is important &#8212; very.',
            'speakers' => [$this->speakerRosterEntry(['name' => 'Larissa Waters'])],
        ]);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('This is important &#8212; very.', $html);
        $this->assertStringContainsString('<cite', $html);
        $this->assertStringContainsString('Larissa Waters', $html);
        $this->assertStringContainsString('Spoke on this topic', $html);
    }

    /**
     *
     */
    public function test_section_index_omits_the_indented_block_entirely_when_there_is_no_excerpt_or_speakers() {
        $item = $this->sectionIndexItem(['excerptHtml' => null, 'speakers' => []]);

        $html = $this->engine->render('hansard/section-index', ['items' => [$item]]);

        $this->assertStringNotContainsString('border-l-2', $html);
        $this->assertStringNotContainsString('Spoke on this topic', $html);
    }

    // -- hansard/speaker-chips -------------------------------------------------

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
        $speaker = $this->speakerRosterEntry([
            'name' => 'Katy Gallagher',
            'description' => 'ALP, Australian Capital Territory & "Territories" Minister',
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

    // -- fixtures ----------------------------------------------------------

    /**
     *
     */
    private function sectionIndexPageHtml(array $overrides = []): string {
        return $this->engine->render('hansard/section-index-page', array_merge([
            'sectionTitle' => 'Adjournment',
            'chamberLabel' => 'House of Representatives',
            'date' => 'Thursday, 20 August 2026',
            'dateUrl' => '/reps/?d=2026-08-20',
            'chamber' => 'House of Representatives',
            'items' => [],
            'nextPrev' => [],
            'aboutTitle' => 'What is the Adjournment?',
            'aboutBodyHtml' => '<p>The Adjournment is important.</p>',
        ], $overrides));
    }

    /**
     *
     */
    private function sectionIndexItem(array $overrides = []): HansardSectionIndexItem {
        $item = new HansardSectionIndexItem();
        $item->titleHtml = 'Climate Change';
        $item->url = '/debates/?id=g108.2';
        $item->countLabel = null;
        $item->excerptHtml = null;
        $item->speakers = [];
        foreach ($overrides as $key => $value) {
            $item->$key = $value;
        }
        return $item;
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
        foreach ($overrides as $key => $value) {
            $entry->$key = $value;
        }
        return $entry;
    }

}
