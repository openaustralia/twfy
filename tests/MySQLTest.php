<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

/**
 * A testable subclass of MySQL that:
 *   - Overrides escape() so no real database connection is needed.
 *   - Exposes build_parameterized_sql() as a public method for direct testing.
 *
 * The escape implementation mirrors what mysqli_real_escape_string does for
 * the characters that matter most for SQL injection.
 */
class TestableMySQL extends MySQL {

    /**
     *
     */
    public function escape($str): string {
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $str
        );
    }

    /**
     *
     */
    public function interpolate(string $sql, array $params): string {
        return $this->build_parameterized_sql($sql, $params);
    }

    /**
     *
     */
    public function span_description(string $sql): string {
        return $this->sql_span_description($sql);
    }

}

/**
 * Tests for MySQL::build_parameterized_sql() — the ? placeholder substitution
 * used by MySQL::query() when extra parameters are supplied.
 *
 * No database connection is required; TestableMySQL provides a pure-PHP
 * escape() implementation.
 */
class MySQLTest extends TestCase {
    private TestableMySQL $db;

    /**
     *
     */
    protected function setUp(): void {
        $this->db = new TestableMySQL();
    }

    // -------------------------------------------------------------------------
    // String parameters

    /**
     * -------------------------------------------------------------------------
     */
    public function test_single_string_param_is_quoted(): void {
        $sql = $this->db->interpolate('WHERE email=?', ['alice@example.com']);
        $this->assertSame("WHERE email='alice@example.com'", $sql);
    }

    /**
     *
     */
    public function test_multiple_string_params_substituted_in_order(): void {
        $sql = $this->db->interpolate(
            'WHERE first=? AND last=?',
            ['Alice', 'Smith']
        );
        $this->assertSame("WHERE first='Alice' AND last='Smith'", $sql);
    }

    /**
     *
     */
    public function test_numeric_string_is_quoted_not_unquoted(): void {
        // A PHP string "42" must stay quoted — only a native int gets unquoted.
        $sql = $this->db->interpolate('WHERE id=?', ['42']);
        $this->assertSame("WHERE id='42'", $sql);
    }

    // -------------------------------------------------------------------------
    // Numeric parameters

    /**
     * -------------------------------------------------------------------------
     */
    public function test_int_param_is_inlined_without_quotes(): void {
        $sql = $this->db->interpolate('WHERE id=?', [42]);
        $this->assertSame('WHERE id=42', $sql);
    }

    /**
     *
     */
    public function test_float_param_is_inlined_without_quotes(): void {
        $sql = $this->db->interpolate('WHERE score > ?', [3.14]);
        $this->assertSame('WHERE score > 3.14', $sql);
    }

    /**
     *
     */
    public function test_negative_int_is_inlined_correctly(): void {
        $sql = $this->db->interpolate('WHERE delta=?', [-5]);
        $this->assertSame('WHERE delta=-5', $sql);
    }

    // -------------------------------------------------------------------------
    // Escaping / injection prevention

    /**
     * -------------------------------------------------------------------------
     */
    public function test_single_quote_in_string_param_is_escaped(): void {
        $sql = $this->db->interpolate('WHERE name=?', ["O'Brien"]);
        $this->assertSame("WHERE name='O\\'Brien'", $sql);
    }

    /**
     *
     */
    public function test_sql_injection_attempt_is_escaped(): void {
        $badness = "' OR '1'='1";
        $sql = $this->db->interpolate('WHERE email=?', [$badness]);
        $expected_escaped = "\' OR \'1\'=\'1";
        $this->assertSame("WHERE email='" . $expected_escaped . "'", $sql);
    }

    /**
     *
     */
    public function test_backslash_in_string_param_is_escaped(): void {
        $sql = $this->db->interpolate('WHERE path=?', ['C:\\Users\\alice']);
        $this->assertSame("WHERE path='C:\\\\Users\\\\alice'", $sql);
    }

    /**
     *
     */
    public function test_newline_in_string_param_is_escaped(): void {
        $sql = $this->db->interpolate('WHERE body=?', ["line1\nline2"]);
        $this->assertSame("WHERE body='line1\\nline2'", $sql);
    }

    // -------------------------------------------------------------------------
    // Mixed int + string params

    /**
     * -------------------------------------------------------------------------
     */
    public function test_mixed_int_and_string_params(): void {
        $sql = $this->db->interpolate(
            'SELECT * FROM users WHERE status=? AND id=?',
            ['active', 7]
        );
        $this->assertSame("SELECT * FROM users WHERE status='active' AND id=7", $sql);
    }

    // -------------------------------------------------------------------------

    /**
     * Edge cases
     */
    public function test_empty_string_param_is_quoted(): void {
        $sql = $this->db->interpolate('WHERE val=?', ['']);
        $this->assertSame("WHERE val=''", $sql);
    }

    /**
     *
     */
    public function test_zero_int_is_inlined(): void {
        $sql = $this->db->interpolate('WHERE count=?', [0]);
        $this->assertSame('WHERE count=0', $sql);
    }

    /**
     *
     */
    public function test_sql_with_no_placeholders_is_unchanged(): void {
        $sql = $this->db->interpolate('SELECT 1', []);
        $this->assertSame('SELECT 1', $sql);
    }

    // -------------------------------------------------------------------------
    // sql_span_description() - must never leak literal query values into the
    // Sentry span it labels.

    /**
     *
     */
    public function test_span_description_for_select_names_the_table(): void {
        $sql = "SELECT * FROM member WHERE email='alice@example.com'";
        $this->assertSame('SELECT member', $this->db->span_description($sql));
    }

    /**
     * A literal value containing the word "FROM" must not be mistaken for
     * the real FROM clause.
     */
    public function test_span_description_ignores_from_inside_a_literal_value(): void {
        $sql = "SELECT id FROM comments WHERE body='FROM the heart, I love you'";
        $this->assertSame('SELECT comments', $this->db->span_description($sql));
    }

    /**
     *
     */
    public function test_span_description_for_insert_names_the_table(): void {
        $sql = "INSERT INTO hansard (epobject_id, gid) VALUES (1, '2026-01-01.1.1')";
        $this->assertSame('INSERT hansard', $this->db->span_description($sql));
    }

    /**
     *
     */
    public function test_span_description_for_replace_names_the_table(): void {
        $sql = 'REPLACE INTO postcode_lookup (postcode, name) VALUES (1, 2)';
        $this->assertSame('REPLACE postcode_lookup', $this->db->span_description($sql));
    }

    /**
     *
     */
    public function test_span_description_for_update_names_the_table(): void {
        $sql = "UPDATE alerts SET email='foo@bar.com' WHERE alert_id=5";
        $this->assertSame('UPDATE alerts', $this->db->span_description($sql));
    }

    /**
     *
     */
    public function test_span_description_for_delete_names_the_table(): void {
        $sql = "DELETE FROM search_query_log WHERE ip_address='1.2.3.4'";
        $this->assertSame('DELETE search_query_log', $this->db->span_description($sql));
    }

    /**
     *
     */
    public function test_span_description_for_unrecognised_verb_falls_back_to_the_verb(): void {
        $this->assertSame('SHOW', $this->db->span_description('SHOW TABLES'));
    }

    /**
     *
     */
    public function test_span_description_never_contains_a_literal_parameter_value(): void {
        $secret = 'super-secret-search-term@example.com';
        $sql = $this->db->interpolate('SELECT * FROM search_query_log WHERE query_string=?', [$secret]);

        $this->assertStringNotContainsString($secret, $this->db->span_description($sql));
    }

}
