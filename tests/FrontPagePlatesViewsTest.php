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
    public function test_latest_activity_column_renders_each_item() {
        $html = $this->engine->render('front/latest-activity-column', [
            'chamberName' => 'House of Representatives',
            'iconColorClass' => 'text-green-700',
            'items' => [
                ['title' => 'Adjournment', 'speaker' => 'Zali Steggall', 'when' => '20 Aug 2026, 1:00 pm', 'url' => '/debates/?id=1'],
            ],
            'dayUrl' => '/debates/?d=2026-08-20',
            'viewAllLabel' => 'the House',
        ]);

        $this->assertStringContainsString('House of Representatives', $html);
        $this->assertStringContainsString('text-green-700', $html);
        $this->assertStringContainsString('Adjournment', $html);
        $this->assertStringContainsString('Spoken by: Zali Steggall', $html);
        $this->assertStringContainsString('href="/debates/?id=1"', $html);
        $this->assertStringContainsString('href="/debates/?d=2026-08-20"', $html);
        $this->assertStringContainsString('the House', $html);
    }

    /**
     *
     */
    public function test_latest_activity_column_omits_the_speaker_line_when_there_is_none() {
        $html = $this->engine->render('front/latest-activity-column', [
            'chamberName' => 'Senate',
            'iconColorClass' => 'text-red-700',
            'items' => [
                ['title' => 'Documents', 'speaker' => '', 'when' => '20 Aug 2026', 'url' => '/senate/?id=1'],
            ],
            'dayUrl' => '/senate/?d=2026-08-20',
            'viewAllLabel' => 'the Senate',
        ]);

        $this->assertStringNotContainsString('Spoken by:', $html);
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
