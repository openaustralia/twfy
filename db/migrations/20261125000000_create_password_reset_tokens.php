<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Laravel's password reset tokens (config/auth.php), in the shape Laravel's own migration makes.
 *
 * The token is stored hashed, one row per email address.
 */
final class CreatePasswordResetTokens extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE password_reset_tokens (
                email varchar(255) NOT NULL,
                token varchar(255) NOT NULL,
                created_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );
    }

    public function down(): void
    {
        $this->table('password_reset_tokens')->drop()->save();
    }
}
