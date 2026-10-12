<?php

namespace OpenAustralia\TWFY;

/**
 * One-off import of addresses that Postal's message history says have
 * hard-failed, so the damage stops before the webhook is switched on.
 *
 * The file is one row per address: the address, the date of its most recent
 * hard fail, and the receiving server's reply exactly as Postal stored it. It
 * comes from the infrastructure export, holds real personal data, and is never
 * committed. The rule for what counts matches the webhook's.
 */
class SuppressionImport {

    const REASON = 'imported_from_postal_history';

    /**
     * Imports a CSV file. Safe to run twice.
     *
     * @param string $path
     *   The CSV file, with or without a header row.
     *
     * @return int[]
     *   Counts only, never addresses: imported, already_suppressed,
     *   skipped_blames_our_server, skipped_no_reply, skipped_already_lifted and invalid.
     */
    public static function fromFile(string $path): array {
        $handle = self::open($path);

        $counts = [
            'imported' => 0,
            'already_suppressed' => 0,
            'skipped_blames_our_server' => 0,
            'skipped_no_reply' => 0,
            'skipped_already_lifted' => 0,
            'invalid' => 0,
        ];

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $outcome = self::importRow($row);
            if ($outcome !== null) {
                $counts[$outcome]++;
            }
        }
        fclose($handle);

        return $counts;
    }

    /**
     * Opens the import file for reading.
     *
     * @param string $path
     *   The CSV file.
     *
     * @return resource
     *   The open file.
     *
     * @throws \OpenAustralia\TWFY\SuppressionImportException
     *   If the file can't be read.
     */
    private static function open(string $path) {
        $handle = is_file($path) && is_readable($path) ? fopen($path, 'r') : false;
        if ($handle === false) {
            throw new SuppressionImportException('Could not read the import file.');
        }
        return $handle;
    }

    /**
     * Imports one row.
     *
     * @param array $row
     *   The row as read from the CSV file.
     *
     * @return string|null
     *   The name of the count this row belongs to, or null for a blank line or
     *   the header row, which aren't counted.
     */
    private static function importRow(array $row): ?string {
        if ($row === [null]) {
            // A blank line.
            return null;
        }
        $address = EmailSuppressions::normalise((string) ($row[0] ?? ''));
        if ($address === 'address') {
            // The header row.
            return null;
        }
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            return 'invalid';
        }

        $reply = trim((string) ($row[2] ?? ''));
        if ($reply === '') {
            // Without the receiving server's reply we can't tell a dead
            // address from a failure that was ours.
            return 'skipped_no_reply';
        }
        if (PostalWebhook::blamesOurServer($reply)) {
            return 'skipped_blames_our_server';
        }

        // Someone lifted this suppression after the failure, so the old
        // failure shouldn't stop their alerts again. A failure after the lift
        // is new and still counts.
        $failedAt = self::failureTime((string) ($row[1] ?? ''));
        if (EmailSuppressions::liftedSince($address, $failedAt)) {
            return 'skipped_already_lifted';
        }

        $suppressed = EmailSuppressions::suppress($address, self::REASON, null, $reply, $failedAt);
        return $suppressed ? 'imported' : 'already_suppressed';
    }

    /**
     * When the failure happened, as Y-m-d H:i:s, or null (meaning now) if the
     * date can't be read. A bad date shouldn't lose a suppression we know is
     * right.
     */
    private static function failureTime(string $date): ?string {
        $timestamp = strtotime(trim($date));
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

}
