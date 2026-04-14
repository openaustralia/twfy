<?php

/**
 * @file
 */

namespace includes;

use MySQL;
use PHPUnit\Framework\TestCase;

/**
 * A testable subclass of MySQL that:
 *   - Overrides escape() so no real database connection is needed.
 *   - Exposes build_parameterized_sql() as a public method for direct testing.
 *
 * The escape implementation mirrors what mysqli_real_escape_string does for
 * the characters that matter most for SQL injection.
 */
class TestableMySQL extends MySQL
{

    /**
     *
     */
    public function escape($str): string
    {
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $str
        );
    }

    /**
     *
     */
    public function interpolate(string $sql, array $params): string
    {
        return $this->build_parameterized_sql($sql, $params);
    }

}

/**
 * Tests for MySQL::build_parameterized_sql() — the ? placeholder substitution
 * used by MySQL::query() when extra parameters are supplied.
 *
 * No database connection is required; TestableMySQL provides a pure-PHP
 * escape() implementation.
 */
class MySQLTest extends TestCase
{
    private TestableMySQL $db;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->db = new TestableMySQL();
    }

    // -------------------------------------------------------------------------
    // String parameters

    /**
     * -------------------------------------------------------------------------
     */
    public function test_single_string_param_is_quoted(): void
    {
        $sql = $this->db->interpolate('WHERE email=?', ['alice@example.com']);
        $this->assertSame("WHERE email='alice@example.com'", $sql);
    }

    /**
     *
     */
    public function test_multiple_string_params_substituted_in_order(): void
    {
        $sql = $this->db->interpolate(
            'WHERE first=? AND last=?',
            ['Alice', 'Smith']
        );
        $this->assertSame("WHERE first='Alice' AND last='Smith'", $sql);
    }

    /**
     *
     */
    public function test_numeric_string_is_quoted_not_unquoted(): void
    {
        // A PHP string "42" must stay quoted — only a native int gets unquoted.
        $sql = $this->db->interpolate('WHERE id=?', ['42']);
        $this->assertSame("WHERE id='42'", $sql);
    }

    // -------------------------------------------------------------------------
    // Numeric parameters

    /**
     * -------------------------------------------------------------------------
     */
    public function test_int_param_is_inlined_without_quotes(): void
    {
        $sql = $this->db->interpolate('WHERE id=?', [42]);
        $this->assertSame('WHERE id=42', $sql);
    }

    /**
     *
     */
    public function test_float_param_is_inlined_without_quotes(): void
    {
        $sql = $this->db->interpolate('WHERE score > ?', [3.14]);
        $this->assertSame('WHERE score > 3.14', $sql);
    }

    /**
     *
     */
    public function test_negative_int_is_inlined_correctly(): void
    {
        $sql = $this->db->interpolate('WHERE delta=?', [-5]);
        $this->assertSame('WHERE delta=-5', $sql);
    }

    // -------------------------------------------------------------------------
    // Escaping / injection prevention

    /**
     * -------------------------------------------------------------------------
     */
    public function test_single_quote_in_string_param_is_escaped(): void
    {
        $sql = $this->db->interpolate('WHERE name=?', ["O'Brien"]);
        $this->assertSame("WHERE name='O\\'Brien'", $sql);
    }

    /**
     *
     */
    public function test_sql_injection_attempt_is_escaped(): void
    {
        $badness = "' OR '1'='1";
        $sql = $this->db->interpolate('WHERE email=?', [$badness]);
        $expected_escaped = "\' OR \'1\'=\'1";
        $this->assertSame("WHERE email='" . $expected_escaped . "'", $sql);
    }

    /**
     *
     */
    public function test_backslash_in_string_param_is_escaped(): void
    {
        $sql = $this->db->interpolate('WHERE path=?', ['C:\\Users\\alice']);
        $this->assertSame("WHERE path='C:\\\\Users\\\\alice'", $sql);
    }

    /**
     *
     */
    public function test_newline_in_string_param_is_escaped(): void
    {
        $sql = $this->db->interpolate('WHERE body=?', ["line1\nline2"]);
        $this->assertSame("WHERE body='line1\\nline2'", $sql);
    }

    // -------------------------------------------------------------------------
    // Mixed int + string params

    /**
     * -------------------------------------------------------------------------
     */
    public function test_mixed_int_and_string_params(): void
    {
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
    public function test_empty_string_param_is_quoted(): void
    {
        $sql = $this->db->interpolate('WHERE val=?', ['']);
        $this->assertSame("WHERE val=''", $sql);
    }

    /**
     *
     */
    public function test_zero_int_is_inlined(): void
    {
        $sql = $this->db->interpolate('WHERE count=?', [0]);
        $this->assertSame('WHERE count=0', $sql);
    }

    /**
     *
     */
    public function test_sql_with_no_placeholders_is_unchanged(): void
    {
        $sql = $this->db->interpolate('SELECT 1', []);
        $this->assertSame('SELECT 1', $sql);
    }

}
