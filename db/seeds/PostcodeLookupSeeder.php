<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds the `postcode_lookup` table from the openaustralia-parser fixtures.
 *
 * The data lives in ../openaustralia-parser/spec/fixtures/postcode_lookup.sql
 * as a list of `INSERT INTO ... VALUES ('<postcode>','<electorate>');` lines.
 * That repo is expected to sit alongside this one in the standard checkout,
 * so we read it directly rather than copying the rows into this tree.
 */
final class PostcodeLookupSeeder extends AbstractSeed {

    /**
     * Candidate locations for the upstream fixture, in priority order.
     * The first is the sibling-repo layout used on host machines; the second
     * is the path used inside docker, where the parent directory is mounted
     * at /work by the docker-db-seed Makefile target.
     */
    private const FIXTURE_CANDIDATES = [
        __DIR__ . '/../../../openaustralia-parser/spec/fixtures/postcode_lookup.sql',
        '/work/openaustralia-parser/spec/fixtures/postcode_lookup.sql',
    ];

    public function run(): void {
        $existing = $this->fetchRow('SELECT COUNT(*) AS c FROM `postcode_lookup`');
        if ((int) ($existing['c'] ?? 0) > 0) {
            return;
        }

        $path = null;
        foreach (self::FIXTURE_CANDIDATES as $candidate) {
            if (is_readable($candidate)) {
                $path = $candidate;
                break;
            }
        }
        if ($path === null) {
            throw new RuntimeException(
                "Could not find postcode_lookup fixture. Looked in:\n  - "
                . implode("\n  - ", self::FIXTURE_CANDIDATES) . "\n"
                . 'Make sure openaustralia-parser is checked out alongside this repo.'
            );
        }

        $fh = fopen($path, 'r');
        if ($fh === false) {
            throw new RuntimeException("Could not open postcode_lookup fixture: $path");
        }

        $pattern = "/^INSERT INTO `postcode_lookup` VALUES \\('([^']+)','([^']+)'\\);/";
        $table = $this->table('postcode_lookup');
        $batch = [];
        while (($line = fgets($fh)) !== false) {
            if (preg_match($pattern, $line, $m) !== 1) {
                continue;
            }
            $batch[] = ['postcode' => $m[1], 'name' => $m[2]];
            if (count($batch) >= 500) {
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
