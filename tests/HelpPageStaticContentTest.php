<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

/**
 * staticpages/help.php is plain markup with no wrapping function, so it's
 * simplest to include it directly and check the rendered text, rather than
 * going through a full (and, for this file, coverage-blind - see
 * PageHeaderAnalyticsTest.php) page render.
 */
class HelpPageStaticContentTest extends TestCase {

    /**
     *
     */
    public function test_help_page_mentions_plausible_not_google_analytics() {
        ob_start();
        include_once __DIR__ . '/../www/includes/easyparliament/staticpages/help.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('Plausible', $html);
        $this->assertStringNotContainsString('urchin', $html);
    }

}
