<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Laravel's database session store (config/session.php), in the shape Laravel's own migration makes.
 *
 * user_id matches users.user_id. Keeping sessions here means the site can list and revoke them.
 */
final class CreateSessions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE sessions (
                id varchar(255) NOT NULL,
                user_id mediumint DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text,
                payload longtext NOT NULL,
                last_activity int NOT NULL,
                PRIMARY KEY (id),
                KEY sessions_user_id_index (user_id),
                KEY sessions_last_activity_index (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->table('sessions')->drop()->save();
    }
}
