<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * One row per request to data.openaustralia.org.au, imported each night from
 * Apache's data_access.log, so downloads outlive the 30 days of logs.
 *
 * line_hash identifies the log line it came from, so importing a file again
 * adds nothing.
 */
final class CreateDataDownloads extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE data_downloads (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                request_time datetime NOT NULL,
                ip_address varchar(45) NOT NULL,
                method varchar(16) NOT NULL,
                path text NOT NULL,
                status smallint unsigned NOT NULL,
                bytes bigint unsigned NOT NULL DEFAULT 0,
                referrer text NOT NULL,
                user_agent text NOT NULL,
                line_hash char(40) NOT NULL,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY line_hash (line_hash),
                KEY request_time (request_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->table('data_downloads')->drop()->save();
    }
}
