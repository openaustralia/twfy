<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Laravel's "remember me" cookie checks this token, and signing out replaces it.
 */
final class AddRememberTokenToUsers extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE users ADD COLUMN remember_token varchar(100) DEFAULT NULL AFTER updated_at');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE users DROP COLUMN remember_token');
    }
}
