<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Errors from importing a day of Hansard, one row each, so a day can have none, one or many.
 *
 * source says which stage found it: "parser" (parse-speeches.rb) or "loader" (xml2db.pl). Each stage clears only its
 * own rows for a day when it next handles that day without an error, so one stage can't wipe the other's.
 */
final class CreateImportErrors extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE import_errors (
                id int unsigned NOT NULL AUTO_INCREMENT,
                hdate date NOT NULL,
                major int NOT NULL,
                source varchar(10) NOT NULL,
                message text NOT NULL,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY day (hdate, major, source)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->table('import_errors')->drop()->save();
    }
}
