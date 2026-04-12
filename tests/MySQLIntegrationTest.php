<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for MySQL::query() with a real database connection.
 * These tests require DB_HOST, DB_USER, DB_PASSWORD, DB_NAME environment variables.
 *
 * A test table is created before each test and dropped after, ensuring isolation.
 */
class MySQLIntegrationTest extends TestCase
{
    private ?MySQL $db = null;
    private bool $has_database = false;

    protected function setUp(): void
    {
        global $TEST_DB;
        if (!$TEST_DB) {
            $this->markTestSkipped('No database configured; set DB_HOST, DB_USER, DB_PASSWORD, DB_NAME');
        }
        $this->db = $TEST_DB;
        $this->has_database = true;
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        if ($this->has_database && $this->db) {
            $this->dropTestTable();
        }
    }

    private function createTestTable(): void
    {
        $sql = "CREATE TEMPORARY TABLE test_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            email VARCHAR(100),
            age INT
        )";
        $this->db->query($sql);
    }

    private function dropTestTable(): void
    {
        $this->db->query("DROP TEMPORARY TABLE IF EXISTS test_users");
    }

    // -------------------------------------------------------------------------
    // INSERT with parameterized queries
    // -------------------------------------------------------------------------

    public function test_insert_with_string_parameter(): void
    {
        $q = $this->db->query(
            "INSERT INTO test_users (name, email) VALUES (?, ?)",
            'Alice',
            'alice@example.com'
        );
        $this->assertTrue($q->success());
        $this->assertGreaterThan(0, $q->insert_id());
    }

    public function test_insert_with_int_parameter(): void
    {
        $q = $this->db->query(
            "INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)",
            'Bob',
            'bob@example.com',
            30
        );
        $this->assertTrue($q->success());
        $this->assertGreaterThan(0, $q->insert_id());
    }

    // -------------------------------------------------------------------------
    // SELECT with parameterized queries
    // -------------------------------------------------------------------------

    public function test_select_with_string_parameter(): void
    {
        // First insert
        $this->db->query(
            "INSERT INTO test_users (name, email) VALUES (?, ?)",
            'Charlie',
            'charlie@example.com'
        );

        // Then select
        $q = $this->db->query(
            "SELECT name, email FROM test_users WHERE email = ?",
            'charlie@example.com'
        );
        $this->assertTrue($q->success());
        $this->assertSame(1, $q->rows());
        $this->assertSame('Charlie', $q->field(0, 'name'));
    }

    public function test_select_with_int_parameter(): void
    {
        // Insert multiple
        $this->db->query(
            "INSERT INTO test_users (name, age) VALUES (?, ?)",
            'Diana',
            25
        );
        $this->db->query(
            "INSERT INTO test_users (name, age) VALUES (?, ?)",
            'Eve',
            25
        );

        // Select by age
        $q = $this->db->query("SELECT name FROM test_users WHERE age = ?", 25);
        $this->assertTrue($q->success());
        $this->assertSame(2, $q->rows());
    }

    // -------------------------------------------------------------------------
    // UPDATE with parameterized queries
    // -------------------------------------------------------------------------

    public function test_update_with_parameters(): void
    {
        $insert_id = $this->db->query(
            "INSERT INTO test_users (name, email) VALUES (?, ?)",
            'Frank',
            'frank@example.com'
        )->insert_id();

        $q = $this->db->query(
            "UPDATE test_users SET email = ? WHERE id = ?",
            'frank.new@example.com',
            $insert_id
        );
        $this->assertTrue($q->success());
        $this->assertSame(1, $q->affected_rows());

        // Verify the update
        $verify = $this->db->query(
            "SELECT email FROM test_users WHERE id = ?",
            $insert_id
        );
        $this->assertSame('frank.new@example.com', $verify->field(0, 'email'));
    }

    // -------------------------------------------------------------------------
    // DELETE with parameterized queries
    // -------------------------------------------------------------------------

    public function test_delete_with_parameter(): void
    {
        $id = $this->db->query(
            "INSERT INTO test_users (name) VALUES (?)",
            'Grace'
        )->insert_id();

        $q = $this->db->query("DELETE FROM test_users WHERE id = ?", $id);
        $this->assertTrue($q->success());
        $this->assertSame(1, $q->affected_rows());

        // Verify deletion
        $verify = $this->db->query("SELECT COUNT(*) as cnt FROM test_users WHERE id = ?", $id);
        $this->assertSame('0', $verify->field(0, 'cnt'));
    }

    // -------------------------------------------------------------------------
    // SQL injection prevention - the key security test
    // -------------------------------------------------------------------------

    public function test_sql_injection_attempt_fails_safely(): void
    {
        // Insert a benign record first
        $this->db->query(
            "INSERT INTO test_users (name, email) VALUES (?, ?)",
            'Hank',
            'hank@example.com'
        );

        // Try an injection attack: where email = "' OR '1'='1"
        // With proper escaping, this should find only one row (the literal email value),
        // not all rows.
        $q = $this->db->query(
            "SELECT COUNT(*) as cnt FROM test_users WHERE email = ?",
            "' OR '1'='1"
        );

        // Should be 0 (no match), not treating it as SQL code
        $this->assertSame('0', $q->field(0, 'cnt'));
    }

    // -------------------------------------------------------------------------
    // Mixed parameter types
    // -------------------------------------------------------------------------

    public function test_mixed_string_and_int_parameters(): void
    {
        $this->db->query(
            "INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)",
            'Ivy',
            'ivy@example.com',
            28
        );

        $q = $this->db->query(
            "SELECT name FROM test_users WHERE email = ? AND age = ?",
            'ivy@example.com',
            28
        );
        $this->assertTrue($q->success());
        $this->assertSame(1, $q->rows());
        $this->assertSame('Ivy', $q->field(0, 'name'));
    }
}
