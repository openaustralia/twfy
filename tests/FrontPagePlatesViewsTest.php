<?php

/**
 * @file
 * Renders each of the new Plates templates under resources/views/front/ (see
 * www/docs/index.php) directly through a real League\Plates\Engine, with plain
 * fixture data - no DB, no $PAGE/$THEUSER globals. Same approach as
 * HansardPlatesViewsTest.php: tests the templates themselves, one test per
 * conditional branch each one has.
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
class FrontPagePlatesViewsTest extends TestCase {

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
    public function test_search_hero_renders_the_form_and_keyword() {
        $html = $this->engine->render('front/search-hero', [
            'searchUrl' => '/search/',
            'keyword' => 'budget',
            'popularSearchesLabel' => null,
        ]);

        $this->assertStringContainsString('action="/search/"', $html);
        $this->assertStringContainsString('value="budget"', $html);
    }

    /**
     *
     */
    public function test_search_hero_omits_the_popular_searches_line_when_there_is_nothing_to_show() {
        $html = $this->engine->render('front/search-hero', [
            'searchUrl' => '/search/',
            'keyword' => '',
            'popularSearchesLabel' => null,
        ]);

        $this->assertStringNotContainsString('Popular searches today', $html);
    }

    /**
     *
     */
    public function test_search_hero_renders_the_popular_searches_line_when_present() {
        $html = $this->engine->render('front/search-hero', [
            'searchUrl' => '/search/',
            'keyword' => '',
            'popularSearchesLabel' => '<a href="/search/?s=house">house</a>',
        ]);

        $this->assertStringContainsString('Popular searches today:', $html);
        $this->assertStringContainsString('<a href="/search/?s=house">house</a>', $html);
    }

    /**
     *
     */
    public function test_feature_icon_renders_the_emoji() {
        $html = $this->engine->render('front/feature-icon', ['emoji' => '👤']);

        $this->assertStringContainsString('👤', $html);
    }

    /**
     *
     */
    public function test_mp_card_renders_the_known_member() {
        $html = $this->engine->render('front/mp-card', [
            'block' => [
                'mode' => 'known',
                'mpName' => 'Zali Steggall',
                'former' => '',
                'mpUrl' => '/mp/?m=1',
                'changeUrl' => '/user/changepc/',
            ],
        ]);

        $this->assertStringContainsString('Your Representative', $html);
        $this->assertStringContainsString('Zali Steggall', $html);
        $this->assertStringContainsString('href="/mp/?m=1"', $html);
        $this->assertStringContainsString('href="/user/changepc/"', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    /**
     *
     */
    public function test_mp_card_renders_a_former_member() {
        $html = $this->engine->render('front/mp-card', [
            'block' => [
                'mode' => 'known',
                'mpName' => 'Someone Retired',
                'former' => 'former',
                'mpUrl' => '/mp/?m=2',
                'changeUrl' => '/user/changepc/',
            ],
        ]);

        $this->assertStringContainsString('your former Federal Representative', $html);
    }

    /**
     *
     */
    public function test_mp_card_renders_the_postcode_form_when_there_is_no_member() {
        $html = $this->engine->render('front/mp-card', ['block' => ['mode' => 'form', 'mpUrl' => '/mp/']]);

        $this->assertStringContainsString('Track Your Reps', $html);
        $this->assertStringContainsString('<form action="/mp/"', $html);
        $this->assertStringNotContainsString('Your Representative', $html);
    }

    /**
     *
     */
    public function test_email_card_renders_the_text() {
        $html = $this->engine->render('front/email-card', ['text' => "Get notified when 'budget' is mentioned in Parliament."]);

        $this->assertStringContainsString('Get Free Email Alerts', $html);
        $this->assertStringContainsString('budget', $html);
    }

    /**
     *
     */
    public function test_feature_row_renders_all_three_cards() {
        $html = $this->engine->render('front/feature-row', [
            'mpBlock' => ['mode' => 'form', 'mpUrl' => '/mp/'],
            'hansardUrl' => '/hansard/',
            'emailAlertUrl' => '/alert/',
            'emailAlertText' => 'Sign up to get an email whenever your representative speaks or a keyword you care about is mentioned.',
        ]);

        $this->assertStringContainsString('Track Your Reps', $html);
        $this->assertStringContainsString('href="/hansard/"', $html);
        $this->assertStringContainsString('Read the Debates', $html);
        $this->assertStringContainsString('href="/alert/"', $html);
        $this->assertStringContainsString('Get Free Email Alerts', $html);
    }

    /**
     *
     */
    public function test_latest_activity_column_renders_the_heading_as_plain_text_not_a_link() {
        $html = $this->latestActivityHtml([
            'items' => [$this->latestActivityItem(['title' => 'Bills'])],
        ]);

        $this->assertStringContainsString('House of Representatives', $html);
        $this->assertStringContainsString('text-green-700', $html);
        $this->assertStringContainsString('Bills', $html);
        $this->assertStringNotContainsString('<a href="" ', $html);
    }

    /**
     * Every "all the titles" link is on something specific now, not the item as a
     * whole - each topic links to its own subsection page.
     */
    public function test_latest_activity_column_links_each_topic_to_its_own_page() {
        $item = $this->latestActivityItem([
            'topics' => [
                ['title' => 'Migration Amendment Bill 2026', 'url' => '/debates/?id=2026-08-20.17.1'],
                ['title' => 'Counter-Terrorism Legislation Amendment Bill 2026', 'url' => '/debates/?id=2026-08-20.25.1'],
            ],
        ]);

        $html = $this->latestActivityHtml(['items' => [$item]]);

        $this->assertStringContainsString('href="/debates/?id=2026-08-20.17.1"', $html);
        $this->assertStringContainsString('Migration Amendment Bill 2026', $html);
        $this->assertStringContainsString('href="/debates/?id=2026-08-20.25.1"', $html);
        $this->assertStringContainsString('Counter-Terrorism Legislation Amendment Bill 2026', $html);
    }

    /**
     *
     */
    public function test_latest_activity_column_shows_how_many_further_topics_did_not_fit() {
        $item = $this->latestActivityItem([
            'topics' => [['title' => 'Migration Amendment Bill 2026', 'url' => '/debates/?id=1']],
            'moreTopicsCount' => 3,
        ]);

        $html = $this->latestActivityHtml(['items' => [$item]]);

        $this->assertStringContainsString('+3 more', $html);
    }

    /**
     *
     */
    public function test_latest_activity_column_omits_the_topics_list_entirely_when_there_are_none() {
        $html = $this->latestActivityHtml([
            'items' => [$this->latestActivityItem(['title' => 'Rearrangement', 'topics' => []])],
        ]);

        $this->assertStringContainsString('Rearrangement', $html);
        $this->assertStringNotContainsString('<ul class="mt-1', $html);
    }

    /**
     * The speaker chip row reuses hansard/speaker-chips.php - full coverage of its
     * own branches (avatar/initials fallback, firstSpeechUrl linking, description
     * tooltip, +N more) lives in HansardPlatesViewsTest.php; this just checks the
     * two components are actually wired together.
     */
    public function test_latest_activity_column_renders_the_speaker_chips_component() {
        $speaker = new HansardSpeakerRosterEntry();
        $speaker->name = 'Zali Steggall';
        $speaker->initials = 'ZS';
        $speaker->firstSpeechUrl = '/debates/?id=2026-08-20.17.1#g17.2';

        $html = $this->latestActivityHtml([
            'items' => [$this->latestActivityItem(['speakers' => [$speaker], 'moreSpeakersCount' => 4])],
        ]);

        $this->assertStringContainsString('Zali Steggall', $html);
        $this->assertStringContainsString('href="/debates/?id=2026-08-20.17.1#g17.2"', $html);
        $this->assertStringContainsString('+4 more', $html);
    }

    /**
     * Not every section has a speaker at all - eg a bare procedural heading with no
     * htype=12 rows under it (see latest_activity_items()'s own doc comment).
     */
    public function test_latest_activity_column_omits_the_speaker_chips_entirely_when_there_are_none() {
        $html = $this->latestActivityHtml([
            'items' => [$this->latestActivityItem(['title' => 'Documents', 'speakers' => []])],
        ]);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('Documents', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function latestActivityHtml(array $overrides = []): string {
        return $this->engine->render('front/latest-activity-column', array_merge([
            'chamberName' => 'House of Representatives',
            'iconColorClass' => 'text-green-700',
            'items' => [],
            'dayUrl' => '/debates/?d=2026-08-20',
            'viewAllLabel' => 'the House',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function latestActivityItem(array $overrides = []): array {
        return array_merge([
            'title' => 'Bills',
            'topics' => [],
            'moreTopicsCount' => 0,
            'speakers' => [],
            'moreSpeakersCount' => 0,
        ], $overrides);
    }

    /**
     *
     */
    public function test_about_this_site_renders_the_about_link() {
        $html = $this->engine->render('front/about-this-site', ['aboutUrl' => '/about/']);

        $this->assertStringContainsString('small charity with a big mission', $html);
        $this->assertStringContainsString('href="/about/"', $html);
        $this->assertStringContainsString('https://donate.oaf.org.au/', $html);
    }

}
