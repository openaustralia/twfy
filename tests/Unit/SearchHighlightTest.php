<?php

use PHPUnit\Framework\TestCase;

require_once INCLUDESPATH . 'easyparliament/search.php';

/**
 * Tests for highlighted_html.
 */
class SearchHighlightTest extends TestCase {

    public function test_empty_searchterm_returns_escaped_text(): void {
        $input = '<script>alert("x")</script> Test';
        $result = highlighted_html($input, '');

        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; Test', $result);
    }

    public function test_matches_are_highlighted_case_insensitively(): void {
        $input = 'Canberra CANBERRA';
        $result = highlighted_html($input, 'canberra');

        $this->assertSame('<span class="hi">Canberra</span> <span class="hi">CANBERRA</span>', $result);
    }

    public function test_regex_characters_in_searchterm_are_treated_literally(): void {
        $input = '<b>A+B?</b>';
        $result = highlighted_html($input, 'A+B?');

        $this->assertSame('&lt;b&gt;<span class="hi">A+B?</span>&lt;/b&gt;', $result);
    }

    public function test_no_match_returns_escaped_text_without_highlight(): void {
        $input = 'Parliament House';
        $result = highlighted_html($input, 'senate');

        $this->assertSame('Parliament House', $result);
    }

}
