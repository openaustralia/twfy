<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Drops uservotes, which held a logged-in person's yes/no vote on a Hansard
 * item. The vote page and the code that read the table have been removed, as
 * nothing on the Australian site could reach them.
 *
 * down() recreates the table empty. The rows are not restored.
 */
final class DropUservotes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('uservotes')->drop()->save();
    }

    public function down(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE `uservotes` (
              `user_id` int unsigned NOT NULL DEFAULT '0',
              `epobject_id` int NOT NULL DEFAULT '0',
              `vote` tinyint(1) NOT NULL DEFAULT '0',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY `epobject_id` (`epobject_id`,`vote`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
            SQL);
    }
}
