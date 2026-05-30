<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Initial schema — loads db/schema.sql verbatim.
 *
 * This captures the existing pre-migration schema as it stood when Phinx was
 * introduced. All subsequent schema changes should be made as new migrations.
 */
final class InitialSchema extends AbstractMigration {

    public function up(): void {
        $schema = file_get_contents(__DIR__ . '/../schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Could not read db/schema.sql');
        }

        // Split on semicolons at end of line and execute each non-empty statement.
        // Comments (-- ...) are fine for the MySQL adapter to receive.
        $statements = preg_split('/;\s*\R/', $schema);
        foreach ($statements as $sql) {
            $sql = trim($sql);
            if ($sql === '' || str_starts_with($sql, '--')) {
                continue;
            }
            $this->execute($sql);
        }
    }

    public function down(): void {
        $tables = [
            'api_stats',
            'api_key',
            'video_timestamps',
            'campaigners_sent_email',
            'campaigners',
            'mentions',
            'uservotes',
            'users',
            'trackbacks',
            'search_query_log',
            'glossary',
            'editqueue',
            'comments',
            'commentreports',
            'anonvotes',
            'alerts',
            'titles',
            'pbc_members',
            'bills',
            'indexbatch',
            'postcode_lookup',
            'personinfo',
            'moffice',
            'memberinfo',
            'member',
            'hansard',
            'gidredirect',
            'epobject',
            'constituency',
            'consinfo',
        ];
        foreach ($tables as $table) {
            $this->execute("DROP TABLE IF EXISTS `$table`");
        }
    }

}
