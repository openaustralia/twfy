<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/FrontPageView.php';

/**
 * FrontPageView (see www/docs/index.php) is the front page's view-model layer -
 * plain data in, plain data out, no DB/global access, no direct echo/print. Same
 * split as HansardSpeechView.php for the debate transcript page (see
 * openaustralia/openaustralia#939).
 */
class FrontPageViewTest extends TestCase {

    /**
     *
     */
    public function test_mpBlock_returns_the_form_mode_when_there_is_no_member() {
        $block = FrontPageView::mpBlock(null, '/mp/', '/user/changepc/');

        $this->assertSame('form', $block['mode']);
        $this->assertSame('/mp/', $block['mpUrl']);
    }

    /**
     *
     */
    public function test_mpBlock_returns_the_known_mode_with_the_members_name() {
        $member = ['first_name' => 'Zali', 'last_name' => 'Steggall', 'still_in_house' => true];

        $block = FrontPageView::mpBlock($member, '/mp/?m=1', '/user/changepc/');

        $this->assertSame('known', $block['mode']);
        $this->assertSame('Zali Steggall', $block['mpName']);
        $this->assertSame('/mp/?m=1', $block['mpUrl']);
        $this->assertSame('/user/changepc/', $block['changeUrl']);
    }

    /**
     *
     */
    public function test_mpBlock_labels_a_former_member_as_former() {
        $current = FrontPageView::mpBlock(
            ['first_name' => 'Zali', 'last_name' => 'Steggall', 'still_in_house' => true],
            '/mp/', '/user/changepc/'
        );
        $former = FrontPageView::mpBlock(
            ['first_name' => 'Zali', 'last_name' => 'Steggall', 'still_in_house' => false],
            '/mp/', '/user/changepc/'
        );

        $this->assertSame('', $current['former']);
        $this->assertSame('former', $former['former']);
    }

    /**
     *
     */
    public function test_emailAlertText_mentions_the_keyword_when_present() {
        $this->assertSame(
            "Get notified when 'budget' is mentioned in Parliament.",
            FrontPageView::emailAlertText('budget')
        );
    }

    /**
     *
     */
    public function test_emailAlertText_falls_back_to_the_generic_message() {
        $this->assertSame(
            'Sign up to get an email whenever your representative speaks or a keyword you care about is mentioned.',
            FrontPageView::emailAlertText(null)
        );
        $this->assertSame(
            'Sign up to get an email whenever your representative speaks or a keyword you care about is mentioned.',
            FrontPageView::emailAlertText('')
        );
    }

    /**
     *
     */
    public function test_emailAlertUrl_includes_the_keyword_when_present() {
        $this->assertSame('/alert?keyword=budget&only=1', FrontPageView::emailAlertUrl('budget'));
    }

    /**
     *
     */
    public function test_emailAlertUrl_falls_back_to_the_plain_alert_page() {
        $this->assertSame('/alert/', FrontPageView::emailAlertUrl(null));
        $this->assertSame('/alert/', FrontPageView::emailAlertUrl(''));
    }

    /**
     *
     */
    public function test_popularSearchesLabel_returns_null_when_there_are_none() {
        $this->assertNull(FrontPageView::popularSearchesLabel([]));
    }

    /**
     * Caught by Sentry's automated review on the PR: genuinely possible, not just
     * theoretical - every one of the day's popular searches individually longer than
     * the budget leaves $correctAmount empty even though $popularSearches itself
     * wasn't, and search-hero.php's !== null check doesn't catch a bare ''.
     */
    public function test_popularSearchesLabel_returns_null_when_every_search_is_too_long_to_fit() {
        $searches = [$this->popularSearch('this-search-term-alone-is-already-too-long', 'x')];

        $this->assertNull(FrontPageView::popularSearchesLabel($searches, 10));
    }

    /**
     *
     */
    public function test_popularSearchesLabel_joins_the_display_html_of_every_search_that_fits() {
        $searches = [
            $this->popularSearch('house', '<a href="/search/?s=house">house</a>'),
            $this->popularSearch('budget', '<a href="/search/?s=budget">budget</a>'),
        ];

        $label = FrontPageView::popularSearchesLabel($searches);

        $this->assertSame('<a href="/search/?s=house">house</a>, <a href="/search/?s=budget">budget</a>', $label);
    }

    /**
     * "house" (5) + "budget" (6) + "environment" (11) = 22, all within the default
     * 32-char budget - but adding a fourth ("infrastructure", 14) would push the
     * total to 36, over budget, so it's skipped even though it's not the first one
     * that would have fit alone.
     */
    public function test_popularSearchesLabel_skips_a_search_that_would_exceed_the_budget() {
        $searches = [
            $this->popularSearch('house', 'house'),
            $this->popularSearch('budget', 'budget'),
            $this->popularSearch('environment', 'environment'),
            $this->popularSearch('infrastructure', 'infrastructure'),
        ];

        $label = FrontPageView::popularSearchesLabel($searches);

        $this->assertSame('house, budget, environment', $label);
    }

    /**
     * A later, shorter search can still fit after an earlier one was skipped -
     * skipping isn't the same as stopping.
     */
    public function test_popularSearchesLabel_keeps_checking_after_skipping_one_that_is_too_long() {
        $searches = [
            $this->popularSearch('this-is-a-very-long-search-term-indeed', 'long'),
            $this->popularSearch('house', 'house'),
        ];

        $label = FrontPageView::popularSearchesLabel($searches, 10);

        $this->assertSame('house', $label);
    }

    /**
     * @return array<string, string>
     */
    private function popularSearch(string $visibleName, string $display): array {
        return ['visible_name' => $visibleName, 'display' => $display];
    }

}
