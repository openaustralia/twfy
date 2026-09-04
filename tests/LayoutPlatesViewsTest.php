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
     * The eyebrow label <li> for each list is always there - only the links
     * themselves are conditional on $helpLinks/$devLinks.
     */
    public function test_footer_renders_no_link_items_when_both_lists_are_empty() {
        $html = $this->engine->render('layout/footer', ['helpLinks' => [], 'devLinks' => []]);

        $this->assertStringNotContainsString('<li></li>', $html);
        $this->assertSame(2, substr_count($html, '<li class="oaf-footer__eyebrow">'));
    }

    /**
     *
     */
    public function test_footer_renders_the_about_blurb_donate_link_and_sibling_projects() {
        $html = $this->engine->render('layout/footer', ['helpLinks' => [], 'devLinks' => []]);

        $this->assertStringContainsString('OpenAustralia.org.au', $html);
        $this->assertStringContainsString('OpenAustralia Foundation', $html);
        $this->assertStringContainsString('href="https://donate.oaf.org.au/"', $html);
        $this->assertStringContainsString('They Vote for You', $html);
        $this->assertStringContainsString('Right to Know', $html);
        $this->assertStringContainsString('Planning Alerts', $html);
    }

    /**
     * The canonical OAF footer's governed attribution block (openaustralia/
     * oaf-standard-footer) - charity/ABN sentence and Acknowledgement of
     * Country, byte-identical to the source repo. Do not edit this expected
     * text without CEO sign-off on the wording change itself.
     */
    public function test_footer_renders_the_governed_attribution_block() {
        $html = $this->engine->render('layout/footer', ['helpLinks' => [], 'devLinks' => []]);

        $this->assertStringContainsString(
            'is a public digital online library and a',
            $html
        );
        $this->assertStringContainsString('ABN&nbsp;<a href="https://www.abr.business.gov.au/ABN/View/24138089942">24&nbsp;138&nbsp;089&nbsp;942</a>', $html);
        $this->assertStringContainsString(
            'OpenAustralia Foundation acknowledges the traditional Owners of Country throughout Australia',
            $html
        );
    }

    /**
     * The ACNC Registered Charity Tick - used under a revocable licence that
     * requires it to link to OAF's own Charity Register entry.
     */
    public function test_footer_renders_the_acnc_tick_linked_to_the_charity_register() {
        $html = $this->engine->render('layout/footer', ['helpLinks' => [], 'devLinks' => []]);

        $this->assertStringContainsString('acnc-registered-charity-colour.svg', $html);
        $this->assertStringContainsString('href="https://www.acnc.gov.au/charity/charities/6bf25724-39af-e811-a960-000d3ad24282/profile"', $html);
    }

    /**
     *
     */
    public function test_footer_renders_the_social_links() {
        $html = $this->engine->render('layout/footer', ['helpLinks' => [], 'devLinks' => []]);

        $this->assertStringContainsString('href="https://github.com/openaustralia"', $html);
        $this->assertStringContainsString('href="https://bsky.app/profile/oaf.org.au"', $html);
        $this->assertStringContainsString('href="https://social.oaf.org.au/@oaf"', $html);
        $this->assertStringContainsString('href="https://www.linkedin.com/company/openaustralia-foundation"', $html);
    }

}
