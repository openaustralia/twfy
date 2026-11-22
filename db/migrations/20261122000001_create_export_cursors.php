<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * The highest id each exporter has sent, by name, so a nightly export carries
 * on where the last one stopped.
 */
final class CreateExportCursors extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE export_cursors (
                name varchar(64) NOT NULL,
                last_id bigint unsigned NOT NULL DEFAULT 0,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->table('export_cursors')->drop()->save();
    }
}
