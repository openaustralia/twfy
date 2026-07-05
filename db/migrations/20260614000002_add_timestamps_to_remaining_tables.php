<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds Eloquent-style `created_at` / `updated_at` columns to all remaining
 * tables that don't have them yet.
 */
final class AddTimestampsToRemainingTables extends AbstractMigration
{
    private const TABLES = [
        'alerts',
        'anonvotes',
        'api_key',
        'api_stats',
        'commentreports',
        'consinfo',
        'editqueue',
        'epobject',
        'gidredirect',
        'glossary',
        'hansard',
        'indexbatch',
        'memberinfo',
        'mentions',
        'moffice',
        'pbc_members',
        'personinfo',
        'postcode_lookup',
        'search_query_log',
        'titles',
        'video_timestamps',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $this->table($tableName);

            if (!$table->hasColumn('created_at')) {
                $table->addColumn('created_at', 'timestamp', [
                    'null' => true,
                    'default' => 'CURRENT_TIMESTAMP',
                ]);
            }

            if (!$table->hasColumn('updated_at')) {
                $table->addColumn('updated_at', 'timestamp', [
                    'null' => true,
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ]);
            }

            $table->update();
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $this->table($tableName);

            if ($table->hasColumn('created_at')) {
                $table->removeColumn('created_at');
            }

            if ($table->hasColumn('updated_at')) {
                $table->removeColumn('updated_at');
            }

            $table->update();
        }
    }
}
