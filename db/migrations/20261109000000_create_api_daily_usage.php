<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * One counter per API key per UTC day. api_log_call() adds to it on every
 * call it logs to api_stats, so the two stay in step, and any finished day
 * can be recounted from api_stats.
 *
 * Making api_key.api_key unique guarantees each key string has one id, so
 * the counter can't be split across duplicates. Production had none.
 *
 * MySQL can't roll back schema changes, so the steps run in an order that
 * is safe to retry: the unique key first, which fails before anything else
 * changes if duplicates exist, and re-adds itself cleanly on a re-run.
 */
final class CreateApiDailyUsage extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE api_key DROP INDEX api_key, ADD UNIQUE KEY api_key (api_key)');
        $this->execute(
            "CREATE TABLE api_daily_usage (
                api_key_id int NOT NULL,
                date date NOT NULL,
                count int unsigned NOT NULL DEFAULT 0,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (api_key_id, date),
                KEY date (date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE api_key DROP INDEX api_key, ADD KEY api_key (api_key)');
        $this->table('api_daily_usage')->drop()->save();
    }
}
