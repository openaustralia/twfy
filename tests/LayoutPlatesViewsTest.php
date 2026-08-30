<?php

/**
 * @file
 * Renders the new resources/views/layout/ templates (site-wide chrome, not tied
 * to any one page - see PAGE::content_end()) directly through a real
 * League\Plates\Engine, with plain fixture data - no DB, no $PAGE/$DATA globals.
 * Same approach as HansardPlatesViewsTest.php/FrontPagePlatesViewsTest.php.
 */

use League\Plates\Engine;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

/**
 *
 */
class LayoutPlatesViewsTest extends TestCase {

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
    public function test_footer_renders_the_help_and_developer_links() {
        $html = $this->engine->render('layout/footer', [
            'helpLinks' => ['<a href="/about/">About us</a>', 'Contact OpenAustralia.org'],
            'devLinks' => ['<a href="/api/">API</a> / <a href="https://data.openaustralia.org.au">XML</a>'],
        ]);

        $this->assertStringContainsString('<a href="/about/">About us</a>', $html);
        // Not linked, same as content_end()'s own "the current page doesn't link to
        // itself" rule (see PAGE::content_end() - $page == $this_page).
        $this->assertStringContainsString('Contact OpenAustralia.org', $html);
        $this->assertStringContainsString('<a href="/api/">API</a>', $html);
    }

    /**
     *
     */
    public function test_footer_renders_no_link_items_when_both_lists_are_empty() {
        $html = $this->engine->render('layout/footer', ['helpLinks' => [], 'devLinks' => []]);

        $this->assertStringNotContainsString('<li>', $html);
    }

    /**
     *
     */
    public function test_footer_renders_the_about_blurb_donate_link_and_sibling_projects() {
        $html = $this->engine->render('layout/footer', ['helpLinks' => [], 'devLinks' => []]);

        $this->assertStringContainsString('OpenAustralia.org.au', $html);
        $this->assertStringContainsString('OpenAustralia Foundation', $html);
        $this->assertStringContainsString('href="https://donate.oaf.org.au/"', $html);
        $this->assertStringContainsString('They Vote For You', $html);
        $this->assertStringContainsString('Right To Know', $html);
        $this->assertStringContainsString('PlanningAlerts', $html);
    }

}
