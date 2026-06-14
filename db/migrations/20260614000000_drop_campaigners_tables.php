<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropCampaignersTables extends AbstractMigration
{
    public function up(): void
    {
        // Drop dependent table first
        $this->execute('DROP TABLE IF EXISTS `campaigners_sent_email`');
        // Then drop main table
        $this->execute('DROP TABLE IF EXISTS `campaigners`');
    }

    public function down(): void
    {
        // Re-creates the table structures as they were in the legacy schema.
        // Note: data dropped by up() is not recoverable.
        $this->execute(<<<'SQL'
            CREATE TABLE `campaigners` (
              `campaigner_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
              `email` varchar(255) NOT NULL DEFAULT '',
              `postcode` varchar(255) NOT NULL DEFAULT '',
              `constituency` varchar(100) NOT NULL DEFAULT '',
              `token` varchar(255) NOT NULL DEFAULT '',
              `confirmed` tinyint(1) NOT NULL DEFAULT '0',
              `signup_date` datetime NOT NULL,
              PRIMARY KEY (`campaigner_id`),
              KEY `email` (`email`),
              KEY `confirmed` (`confirmed`),
              KEY `constituency` (`constituency`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE `campaigners_sent_email` (
              `campaigner_id` int NOT NULL,
              `email_name` varchar(100) NOT NULL,
              UNIQUE KEY `campaigner_id` (`campaigner_id`,`email_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        SQL);
    }
}
