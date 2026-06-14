<?php

use PHPUnit\Framework\TestCase;

/**
 *
 */
abstract class DatabaseIntegrationTestCase extends TestCase {
    protected ?MySQL $db = null;

    final protected function setUp(): void {
        $this->db = TestDatabase::tryConnect();
        if (!$this->db) {
            $this->markTestSkipped('No database configured or connection failed; set DB_HOST, DB_USER, DB_PASSWORD, DB_NAME');
        }

        $this->createTemporaryTables();
    }

    final protected function tearDown(): void {
        if ($this->db) {
            $this->dropTemporaryTables();
        }
    }

    abstract protected function createTemporaryTables(): void;

    abstract protected function dropTemporaryTables(): void;

}
