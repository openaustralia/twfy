<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTimestampsToModels extends AbstractMigration
{
    public function up(): void
    {
        // Add timestamps to bills table
        $this->execute(<<<'SQL'
            ALTER TABLE `bills`
            ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        SQL);

        // Add timestamps to constituency table
        $this->execute(<<<'SQL'
            ALTER TABLE `constituency`
            ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        SQL);

        // Add updated_at to comments (posted already exists for creation)
        $this->execute(<<<'SQL'
            ALTER TABLE `comments`
            ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        SQL);

        // Add timestamps to uservotes table
        $this->execute(<<<'SQL'
            ALTER TABLE `uservotes`
            ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        SQL);
    }

    public function down(): void
    {
        // Remove timestamps from bills
        $this->execute('ALTER TABLE `bills` DROP COLUMN `created_at`, DROP COLUMN `updated_at`');

        // Remove timestamps from constituency
        $this->execute('ALTER TABLE `constituency` DROP COLUMN `created_at`, DROP COLUMN `updated_at`');

        // Remove updated_at from comments
        $this->execute('ALTER TABLE `comments` DROP COLUMN `updated_at`');

        // Remove timestamps from uservotes
        $this->execute('ALTER TABLE `uservotes` DROP COLUMN `created_at`, DROP COLUMN `updated_at`');
    }
}
