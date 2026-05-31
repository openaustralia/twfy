<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds the `member` table from db/seeds/data/member.csv.
 *
 * The CSV has a header row matching the `member` table column names. Empty
 * cells on nullable columns are inserted as NULL.
 *
 * To regenerate the CSV from the current docker mysql:
 *
 *   docker compose exec -T mysql mysql -utwfyuser -ptwfypass --batch --raw -N twfy \
 *     -e "SELECT member_id,house,first_name,last_name,constituency,party,\
 *                entered_house,left_house,entered_reason,left_reason,\
 *                person_id,title FROM member ORDER BY member_id" > /tmp/member.tsv
 *
 *   php -r 'use ...; (convert /tmp/member.tsv to db/seeds/data/member.csv,
 *           prepending a header row and mapping "NULL" -> empty)'
 */
final class MemberSeeder extends AbstractSeed {

    private const DATA_FILE = __DIR__ . '/data/member.csv';
    private const BATCH_SIZE = 500;
    /** Columns that may legitimately be NULL in the schema. */
    private const NULLABLE_COLUMNS = ['house', 'first_name'];

    public function run(): void {
        // Don't clobber an existing populated table.
        $existing = $this->fetchRow('SELECT COUNT(*) AS c FROM `member`');
        if ((int) ($existing['c'] ?? 0) > 0) {
            return;
        }

        $fh = fopen(self::DATA_FILE, 'r');
        if ($fh === false) {
            throw new RuntimeException('Could not open seed data: ' . self::DATA_FILE);
        }

        $header = fgetcsv($fh, 0, ',', '"', '\\');
        if ($header === false) {
            fclose($fh);
            throw new RuntimeException('Seed CSV is empty: ' . self::DATA_FILE);
        }

        $table = $this->table('member');
        $batch = [];
        while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            if ($row === [null]) {
                continue; // blank line
            }
            $assoc = array_combine($header, $row);
            foreach (self::NULLABLE_COLUMNS as $col) {
                if (($assoc[$col] ?? '') === '') {
                    $assoc[$col] = null;
                }
            }
            $batch[] = $assoc;
            if (count($batch) >= self::BATCH_SIZE) {
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
