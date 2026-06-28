<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for the MySQLQuery result-accessor methods.
 *
 * MySQLQuery::query() requires a live database connection and is covered by
 * integration tests. These unit tests exercise all of the accessor methods
 * (success, rows, field, row, insert_id, affected_rows) by pre-populating the
 * public properties that query() would normally set, so no database is needed.
 */
class MySQLQueryTest extends TestCase {

    private function makeQuery(): MySQLQuery {
        // Null connection is fine — constructor just stores it, and none of the
        // accessor methods use it.
        return new MySQLQuery(null);
    }

    // -------------------------------------------------------------------------
    // Default state
    // -------------------------------------------------------------------------

    public function test_success_is_true_by_default(): void {
        $q = $this->makeQuery();
        $this->assertTrue($q->success());
    }

    public function test_rows_is_null_by_default(): void {
        $q = $this->makeQuery();
        $this->assertNull($q->rows());
    }

    public function test_insert_id_is_null_by_default(): void {
        $q = $this->makeQuery();
        $this->assertNull($q->insert_id());
    }

    public function test_affected_rows_is_null_by_default(): void {
        $q = $this->makeQuery();
        $this->assertNull($q->affected_rows());
    }

    // -------------------------------------------------------------------------
    // field()
    // -------------------------------------------------------------------------

    public function test_field_returns_empty_string_when_rows_is_zero(): void {
        $q = $this->makeQuery();
        $q->rows = 0;
        $this->assertSame('', $q->field(0, 'name'));
    }

    public function test_field_returns_correct_string_value(): void {
        $q = $this->makeQuery();
        $q->rows = 1;
        $q->fieldnames_byname = ['name' => 0, 'email' => 1];
        $q->data = [['Alice', 'alice@example.com']];

        $this->assertSame('Alice', $q->field(0, 'name'));
        $this->assertSame('alice@example.com', $q->field(0, 'email'));
    }

    public function test_field_returns_correct_value_from_second_row(): void {
        $q = $this->makeQuery();
        $q->rows = 2;
        $q->fieldnames_byname = ['name' => 0];
        $q->data = [['Alice'], ['Bob']];

        $this->assertSame('Bob', $q->field(1, 'name'));
    }

    // -------------------------------------------------------------------------
    // row()
    // -------------------------------------------------------------------------

    public function test_row_returns_associative_array(): void {
        $q = $this->makeQuery();
        $q->rows = 1;
        $q->fieldnames_byid = [0 => 'id', 1 => 'name'];
        $q->fieldnames_byname = ['id' => 0, 'name' => 1];
        $q->data = [['42', 'Alice']];

        $this->assertSame(['id' => '42', 'name' => 'Alice'], $q->row(0));
    }

    public function test_row_returns_correct_row_by_index(): void {
        $q = $this->makeQuery();
        $q->rows = 2;
        $q->fieldnames_byid = [0 => 'name'];
        $q->fieldnames_byname = ['name' => 0];
        $q->data = [['Alice'], ['Bob']];

        $this->assertSame(['name' => 'Bob'], $q->row(1));
    }

    public function test_row_returns_empty_array_when_not_successful(): void {
        $q = $this->makeQuery();
        $q->success = false;
        $this->assertSame([], $q->row(0));
    }

    public function test_row_returns_empty_array_when_no_rows(): void {
        $q = $this->makeQuery();
        $q->rows = 0;
        $this->assertSame([], $q->row(0));
    }

    // -------------------------------------------------------------------------
    // insert / affected (non-SELECT results)
    // -------------------------------------------------------------------------

    public function test_insert_id_returns_set_value(): void {
        $q = $this->makeQuery();
        $q->insert_id = 99;
        $this->assertSame(99, $q->insert_id());
    }

    public function test_affected_rows_returns_set_value(): void {
        $q = $this->makeQuery();
        $q->affected_rows = 3;
        $this->assertSame(3, $q->affected_rows());
    }

}
