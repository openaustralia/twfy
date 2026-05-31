<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

/**
 * Initial schema — loads db/schema.sql verbatim.
 *
 * This captures the existing pre-migration schema as it stood when Phinx was
 * introduced. All subsequent schema changes should be made as new migrations.
 */
final class InitialSchema extends AbstractMigration {
    function shouldRun(): bool {
        // Only run this migration if the 'member' table doesn't exist, which is a good proxy for whether the schema has been set up at all.
        $exists = $this->hasTable('member');
        if ($exists) {
            echo "Skipping InitialSchema migration because 'member' table already exists.\n";
        }
        return !$exists;
    }

    public function up(): void {
        if (!$this->shouldRun()) {
            return;
        }


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
        throw new IrreversibleMigrationException(
            'InitialSchema is irreversible: it loads the entire baseline schema from db/schema.sql.'
        );
    }
}
