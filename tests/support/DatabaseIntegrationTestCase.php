<?php

namespace support;

use PHPUnit\Framework\TestCase;

/**
 *
 */
abstract class DatabaseIntegrationTestCase extends TestCase {
    protected ?\MySQL $db = NULL;

    /**
     *
     */
    final protected function setUp(): void {
        $this->db = \TestDatabase::tryConnect();
        if (!$this->db) {
            $this->markTestSkipped('No database configured or connection failed; set DB_HOST, DB_USER, DB_PASSWORD, DB_NAME');
        }

        $this->createTemporaryTables();
        $this->setUpData();
    }

    /**
     *
     */
    final protected function tearDown(): void {
        if ($this->db) {
            $this->dropTemporaryTables();
        }
    }

    /**
     * define in tests to create the tables you are testing, for example, use
     * $this->createTemporaryTablesFromSchema('users', 'alerts');
     */
    abstract protected function createTemporaryTables(): void;

    /**
     * define in tests to drop the tables you are testing, for example, use
     * $this->dropTemporaryTablesIfExists('users');
     */
    abstract protected function dropTemporaryTables(): void;

    /**
     * define in tests to set up the data you are testing, for example, use
     * $this->loadFixtures('users', 'alerts');
     */
    protected function setUpData(): void {
    }

    /**
     * Create temporary tables based on the definitions in the schema.sql file.
     */
    protected function createTemporaryTablesFromSchema(string ...$tableNames): void {
        $schema = file_get_contents(__DIR__ . '/../../db/schema.sql');
        foreach ($tableNames as $tableName) {
            preg_match('/CREATE TABLE `' . preg_quote($tableName, '/') . '`.*?;/s', $schema, $matches);
            if (empty($matches)) {
                throw new \RuntimeException("Table '$tableName' not found in schema.sql");
            }
            $sql = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $matches[0]);
            $this->db->query($sql);
        }
    }

    /**
     * Drop temporary tables based on the definitions in the schema.sql file.
     */
    protected function dropTemporaryTablesIfExists(string ...$tableNames): void {
        foreach ($tableNames as $tableName) {
            $this->db->query("DROP TEMPORARY TABLE IF EXISTS " . $tableName);
        }
    }

    /**
     * Create and Load fixtures from the fixtures directory as temporary tables.
     */
    protected function loadFixtures(string ...$tableNames): void {
        foreach ($tableNames as $tableName) {
            $file = __DIR__ . '/../fixtures/' . $tableName . '.sql';
            if (!file_exists($file)) {
                throw new \RuntimeException("Fixture '$tableName.sql' not found");
            }
            $sql = file_get_contents($file);
            $sql = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $sql);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                $this->db->query($statement);
            }
        }
    }

}
