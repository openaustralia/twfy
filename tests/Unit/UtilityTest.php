<?php

use PHPUnit\Framework\TestCase;

require_once INCLUDESPATH . 'utility.php';

/**
 * Unit tests for utility helpers.
 */
class UtilityTest extends TestCase {

    // --- twfy_debug ---

    public function test_twfy_debug_outputs_when_debug_level_is_not_empty(): void {
        $oldGet = $_GET;
        $oldPost = $_POST;

        try {
            $_GET = [DEBUGTAG => '1'];
            $_POST = [];

            // Start output buffering to capture the output of twfy_debug.
            ob_start();
            twfy_debug('PAGE', 'debug message');
            $output = ob_get_clean();

            $this->assertIsString($output);
            $this->assertStringContainsString('PAGE', $output);
            $this->assertStringContainsString('debug message', $output);
        } finally {
            $_GET = $oldGet;
            $_POST = $oldPost;
        }
    }

    // --- validate_email ---

    public function test_validate_email_accepts_valid_email(): void {
        $this->assertTrue(validate_email('user@example.com'));
    }

    public function test_validate_email_accepts_plus_addressing(): void {
        $this->assertTrue(validate_email('user+tag@example.com'));
    }

    public function test_validate_email_rejects_missing_at(): void {
        $this->assertFalse(validate_email('userexample.com'));
    }

    public function test_validate_email_rejects_empty_string(): void {
        $this->assertFalse(validate_email(''));
    }

    public function test_validate_email_rejects_no_tld(): void {
        $this->assertFalse(validate_email('user@localhost'));
    }

    // --- validate_postcode ---

    public function test_validate_postcode_accepts_four_digit_postcode(): void {
        $this->assertTrue(validate_postcode('2000'));
    }

    public function test_validate_postcode_accepts_with_trailing_spaces(): void {
        $this->assertTrue(validate_postcode('  3000  '));
    }

    public function test_validate_postcode_rejects_three_digits(): void {
        $this->assertFalse(validate_postcode('200'));
    }

    public function test_validate_postcode_rejects_letters(): void {
        $this->assertFalse(validate_postcode('ABCD'));
    }

    public function test_validate_postcode_rejects_empty(): void {
        $this->assertFalse(validate_postcode(''));
    }

    // --- format_date ---

    public function test_format_date_returns_formatted_date(): void {
        $this->assertSame('28 Feb 2003', format_date('2003-02-28', 'j M Y'));
    }

    public function test_format_date_returns_empty_for_invalid(): void {
        $this->assertSame('', format_date('not-a-date', 'Y-m-d'));
    }

    public function test_format_date_returns_empty_for_empty_string(): void {
        $this->assertSame('', format_date('', 'Y-m-d'));
    }

    // --- format_time ---

    public function test_format_time_returns_formatted_time(): void {
        $this->assertSame('14:30', format_time('14:30:00', 'H:i'));
    }

    public function test_format_time_returns_empty_for_invalid(): void {
        $this->assertSame('', format_time('not-a-time', 'H:i'));
    }

    public function test_format_time_returns_empty_for_empty_string(): void {
        $this->assertSame('', format_time('', 'H:i'));
    }

    // --- make_ranking ---

    public function test_make_ranking_1st(): void {
        $this->assertSame('1st', make_ranking(1));
    }

    public function test_make_ranking_2nd(): void {
        $this->assertSame('2nd', make_ranking(2));
    }

    public function test_make_ranking_3rd(): void {
        $this->assertSame('3rd', make_ranking(3));
    }

    public function test_make_ranking_4th(): void {
        $this->assertSame('4th', make_ranking(4));
    }

    public function test_make_ranking_11th(): void {
        $this->assertSame('11th', make_ranking(11));
    }

    public function test_make_ranking_12th(): void {
        $this->assertSame('12th', make_ranking(12));
    }

    public function test_make_ranking_13th(): void {
        $this->assertSame('13th', make_ranking(13));
    }

    public function test_make_ranking_21st(): void {
        $this->assertSame('21st', make_ranking(21));
    }

    public function test_make_ranking_112th(): void {
        $this->assertSame('112th', make_ranking(112));
    }

    // --- make_plural ---

    public function test_make_plural_singular(): void {
        $this->assertSame('word', make_plural('word', 1));
    }

    public function test_make_plural_plural(): void {
        $this->assertSame('words', make_plural('word', 2));
    }

    public function test_make_plural_zero(): void {
        $this->assertSame('words', make_plural('word', 0));
    }

    // --- make_member_url ---

    public function test_make_member_url_simple_name(): void {
        $this->assertSame('john_smith', make_member_url('John Smith'));
    }

    public function test_make_member_url_with_constituency(): void {
        $result = make_member_url('John Smith', 'Sydney', HOUSE::REPRESENTATIVES);
        $this->assertSame('john_smith/sydney', $result);
    }

    public function test_make_member_url_with_senate(): void {
        $result = make_member_url('Jane Doe', 'Victoria', HOUSE::SENATE);
        $this->assertSame('jane_doe/victoria', $result);
    }

    public function test_make_member_url_strips_the_prefix(): void {
        $result = make_member_url('The Speaker', '', HOUSE::REPRESENTATIVES);
        $this->assertSame('speaker', $result);
    }

    public function test_make_member_url_replaces_entities(): void {
        $result = make_member_url('Andr&eacute; Smith', '', HOUSE::REPRESENTATIVES);
        $this->assertStringNotContainsString('&', $result);
    }

    public function test_make_member_url_no_constituency_appended_for_invalid_house(): void {
        $result = make_member_url('John Smith', 'Sydney', 99);
        $this->assertSame('john_smith', $result);
    }

    // --- member_full_name ---

    public function test_member_full_name_representative_with_title(): void {
        $this->assertSame('Hon John Smith', member_full_name(HOUSE::REPRESENTATIVES, 'Hon', 'John', 'Smith', 'Sydney'));
    }

    public function test_member_full_name_representative_no_title(): void {
        $this->assertSame('John Smith', member_full_name(HOUSE::REPRESENTATIVES, '', 'John', 'Smith', 'Sydney'));
    }

    public function test_member_full_name_senate(): void {
        $this->assertSame('Sen Jane Doe', member_full_name(HOUSE::SENATE, 'Sen', 'Jane', 'Doe', 'Victoria'));
    }

    public function test_member_full_name_invalid_house_still_returns_name(): void {
        $this->assertSame('John Smith', member_full_name(99, '', 'John', 'Smith', 'Nowhere'));
    }

    // --- fix_gid_from_db ---

    public function test_fix_gid_from_db_strips_prefix(): void {
        $this->assertSame('2003-02-28.475.3', fix_gid_from_db('uk.org.publicwhip/debate/2003-02-28.475.3'));
    }

    public function test_fix_gid_from_db_keepmajor(): void {
        $this->assertSame('debate_2003-02-28.475.3', fix_gid_from_db('uk.org.publicwhip/debate/2003-02-28.475.3', true));
    }

    // --- gid_to_anchor ---

    public function test_gid_to_anchor(): void {
        $this->assertSame('475.3', gid_to_anchor('2003-02-28.475.3'));
    }

    // --- strip_tags_tospaces ---

    public function test_strip_tags_tospaces_replaces_block_tags(): void {
        $this->assertSame('Hello World', strip_tags_tospaces('<p>Hello</p><p>World</p>'));
    }

    public function test_strip_tags_tospaces_strips_inline_tags(): void {
        $this->assertSame('Hello World', strip_tags_tospaces('<b>Hello</b> <i>World</i>'));
    }

    // --- entities_to_numbers ---

    public function test_entities_to_numbers_converts_ouml(): void {
        $this->assertSame('&#214;', entities_to_numbers('&Ouml;'));
    }

    public function test_entities_to_numbers_converts_acirc(): void {
        $this->assertSame('&#226;', entities_to_numbers('&acirc;'));
    }

    public function test_entities_to_numbers_leaves_plain_text(): void {
        $this->assertSame('hello', entities_to_numbers('hello'));
    }

    // --- prettify_office ---

    public function test_prettify_office_with_pos_and_dept(): void {
        $this->assertSame('Minister, Defence', prettify_office('Minister', 'Defence'));
    }

    public function test_prettify_office_with_lookup_match(): void {
        $this->assertSame('Prime Minister', prettify_office('Prime Minister', 'HM Treasury'));
    }

    public function test_prettify_office_pos_only(): void {
        $this->assertSame('Speaker', prettify_office('Speaker', ''));
    }

    public function test_prettify_office_dept_only(): void {
        $this->assertSame('Member, Defence Committee', prettify_office('', 'Defence Committee'));
    }

    // --- htmlentities_notags ---

    public function test_htmlentities_notags_encodes_ampersand(): void {
        $this->assertSame('Tom &amp; Jerry', htmlentities_notags('Tom & Jerry'));
    }

    public function test_htmlentities_notags_preserves_html_tags(): void {
        $result = htmlentities_notags('<b>bold</b>');
        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('</b>', $result);
    }

    public function test_htmlentities_notags_removes_windows_1252_chars(): void {
        // \x93 is a Windows-1252 left double quotation mark.
        $result = htmlentities_notags("hello\x93world");
        $this->assertStringNotContainsString("\x93", $result);
    }

    // --- trim_characters ---

    public function test_trim_characters_no_trimming_needed(): void {
        $this->assertSame('short text', trim_characters('short text', 0, 100));
    }

    public function test_trim_characters_trims_end_with_ellipsis(): void {
        $result = trim_characters('This is a somewhat longer piece of text that should be trimmed', 0, 30);
        $this->assertLessThanOrEqual(30, strlen($result));
        $this->assertStringEndsWith('...', $result);
    }

    public function test_trim_characters_trims_start_with_ellipsis(): void {
        $result = trim_characters('This is a somewhat longer piece of text', 10, 100);
        $this->assertStringStartsWith('...', $result);
    }

    public function test_trim_characters_strips_html(): void {
        $result = trim_characters('<p>Hello <b>world</b></p>', 0, 100);
        $this->assertStringNotContainsString('<', $result);
    }

    // --- majorSummary ---

    public function test_major_summary_renders_empty_list_for_empty_data(): void {
        ob_start();
        majorSummary([]);
        $output = ob_get_clean();

        $this->assertSame('<ul id="hansard-day"></ul>', $output);
    }

    public function test_major_summary_renders_same_output_for_empty_with_explicit_limit(): void {
        ob_start();
        majorSummary([]);
        $defaultOutput = ob_get_clean();

        ob_start();
        majorSummary([], 10);
        $limitedOutput = ob_get_clean();

        $this->assertSame($defaultOutput, $limitedOutput);
    }

}
