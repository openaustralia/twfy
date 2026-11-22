<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * A record of each change to a user's role (users.status), made on the admin Users page.
 *
 * changed_by is the Superuser who made it. Neither user id has a foreign key, matching the rest of the schema; users
 * are marked deleted, not removed.
 */
final class CreateUserRoleChanges extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE user_role_changes (
                id int unsigned NOT NULL AUTO_INCREMENT,
                user_id mediumint NOT NULL,
                changed_by mediumint NOT NULL,
                from_role varchar(20) NOT NULL,
                to_role varchar(20) NOT NULL,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->table('user_role_changes')->drop()->save();
    }
}
