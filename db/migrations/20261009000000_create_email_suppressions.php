<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * One row per suppression of an email address. Lifting a suppression sets
 * lifted_at and keeps the row, so the history stays. active_email is NULL
 * once lifted, so the unique key allows at most one active suppression per
 * address while still allowing any number of lifted ones.
 */
final class CreateEmailSuppressions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE email_suppressions (
                suppression_id int unsigned NOT NULL AUTO_INCREMENT,
                email varchar(255) NOT NULL,
                reason varchar(32) NOT NULL,
                postal_event varchar(64) DEFAULT NULL,
                reply_excerpt varchar(255) DEFAULT NULL,
                suppressed_at datetime NOT NULL,
                lifted_at datetime DEFAULT NULL,
                active_email varchar(255) GENERATED ALWAYS AS (IF(lifted_at IS NULL, email, NULL)) VIRTUAL,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (suppression_id),
                UNIQUE KEY active_email (active_email),
                KEY email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->table('email_suppressions')->drop()->save();
    }
}
