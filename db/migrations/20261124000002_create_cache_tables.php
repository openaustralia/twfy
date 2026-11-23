<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Laravel's database cache store (config/cache.php), in the shape Laravel's own migration makes.
 *
 * Login throttling counts attempts here, as Apache can't write to the release directory for a file cache.
 */
final class CreateCacheTables extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE cache (
                `key` varchar(255) NOT NULL,
                value mediumtext NOT NULL,
                expiration int NOT NULL,
                PRIMARY KEY (`key`),
                KEY cache_expiration_index (expiration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
        $this->execute(
            "CREATE TABLE cache_locks (
                `key` varchar(255) NOT NULL,
                owner varchar(255) NOT NULL,
                expiration int NOT NULL,
                PRIMARY KEY (`key`),
                KEY cache_locks_expiration_index (expiration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->table('cache_locks')->drop()->save();
        $this->table('cache')->drop()->save();
    }
}
