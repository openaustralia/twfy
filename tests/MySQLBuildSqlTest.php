<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for MySQL::build_parameterized_sql().
 *
 * Requires a live database connection for escape() to work.
 * Tests are skipped automatically when no connection is available.
 */
class MySQLBuildSqlTest extends TestCase
{
    private ?MySQL $db;
    private ReflectionMethod $method;

    protected function setUp(): void
    {
        $this->db = TestDatabase::tryConnect();
        if (!$this->db) {
            $this->markTestSkipped('No database connection available.');
        }
        $m = new ReflectionMethod(MySQL::class, 'build_parameterized_sql');
        $m->setAccessible(true);
        $this->method = $m;
    }

    private function build(string $sql, array $params): string
    {
        return $this->method->invoke($this->db, $sql, $params);
    }

    // -------------------------------------------------------------------------
    // Scalar values
    // -------------------------------------------------------------------------

    public function test_string_is_quoted_and_escaped(): void
    {
        $this->assertSame("SELECT * FROM t WHERE x = 'hello'",
            $this->build('SELECT * FROM t WHERE x = ?', ['hello']));
    }

    public function test_string_with_single_quote_is_escaped(): void
    {
        $this->assertSame("SELECT * FROM t WHERE x = 'it\\'s'",
            $this->build('SELECT * FROM t WHERE x = ?', ["it's"]));
    }

    public function test_integer_is_unquoted(): void
    {
        $this->assertSame('SELECT * FROM t WHERE id = 42',
            $this->build('SELECT * FROM t WHERE id = ?', [42]));
    }

    public function test_float_is_unquoted(): void
    {
        $this->assertSame('SELECT * FROM t WHERE x = 3.14',
            $this->build('SELECT * FROM t WHERE x = ?', [3.14]));
    }

    // -------------------------------------------------------------------------
    // Multiple placeholders
    // -------------------------------------------------------------------------

    public function test_multiple_scalar_placeholders(): void
    {
        $this->assertSame("UPDATE t SET name = 'Alice' WHERE id = 1",
            $this->build('UPDATE t SET name = ? WHERE id = ?', ['Alice', 1]));
    }

    // -------------------------------------------------------------------------
    // Array expansion (IN (?))
    // -------------------------------------------------------------------------

    public function test_array_of_ints_expands_unquoted(): void
    {
        $this->assertSame('SELECT * FROM t WHERE id IN (1, 2, 3)',
            $this->build('SELECT * FROM t WHERE id IN (?)', [[1, 2, 3]]));
    }

    public function test_array_of_strings_expands_quoted(): void
    {
        $this->assertSame("SELECT * FROM t WHERE x IN ('a', 'b', 'c')",
            $this->build('SELECT * FROM t WHERE x IN (?)', [['a', 'b', 'c']]));
    }

    public function test_array_of_mixed_types_expands_correctly(): void
    {
        $this->assertSame("SELECT * FROM t WHERE x IN (1, 'b', 3)",
            $this->build('SELECT * FROM t WHERE x IN (?)', [[1, 'b', 3]]));
    }

    public function test_array_with_string_needing_escape(): void
    {
        $this->assertSame("SELECT * FROM t WHERE x IN ('it\\'s', 'fine')",
            $this->build('SELECT * FROM t WHERE x IN (?)', [["it's", 'fine']]));
    }

    // -------------------------------------------------------------------------
    // Array alongside scalar placeholders
    // -------------------------------------------------------------------------

    public function test_array_and_scalar_placeholders_together(): void
    {
        $this->assertSame("SELECT * FROM t WHERE id IN (1, 2) AND status = 'active'",
            $this->build('SELECT * FROM t WHERE id IN (?) AND status = ?', [[1, 2], 'active']));
    }
}
