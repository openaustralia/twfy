<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Helper for seeders that load rows from a header-row CSV under db/seeds/data/.
 *
 * Skips seeding if the target table already contains rows so re-running is safe.
 * Treats empty cells on the listed nullable columns as NULL.
 */
trait CsvSeederTrait {

    /**
     * @param  AbstractSeed  $seeder
     * @param  string        $tableName    DB table to insert into.
     * @param  string        $csvBasename  Filename under db/seeds/data/, no path.
     * @param  array<string> $nullableColumns Columns where '' should become NULL.
     * @param  int           $batchSize
     */
    protected function loadCsv(
        AbstractSeed $seeder,
        string $tableName,
        string $csvBasename,
        array $nullableColumns = [],
        int $batchSize = 500
    ): void {
        $existing = $seeder->fetchRow("SELECT COUNT(*) AS c FROM `$tableName`");
        if ((int) ($existing['c'] ?? 0) > 0) {
            return;
        }

        $path = __DIR__ . '/../data/' . $csvBasename;
        $fh = fopen($path, 'r');
        if ($fh === false) {
            throw new RuntimeException("Could not open seed data: $path");
        }

        $header = fgetcsv($fh, 0, ',', '"', '\\');
        if ($header === false) {
            fclose($fh);
            throw new RuntimeException("Seed CSV is empty: $path");
        }

        $nullable = array_flip($nullableColumns);
        $table = $seeder->table($tableName);
        $batch = [];
        while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            if ($row === [null]) {
                continue;
            }
            $assoc = array_combine($header, $row);
            foreach ($nullable as $col => $_) {
                if (($assoc[$col] ?? '') === '') {
                    $assoc[$col] = null;
                }
            }
            $batch[] = $assoc;
            if (count($batch) >= $batchSize) {
                $table->insert($batch)->saveData();
                $batch = [];
            }
        }
        if (!empty($batch)) {
            $table->insert($batch)->saveData();
        }
        fclose($fh);
    }

}
