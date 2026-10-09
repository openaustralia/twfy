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
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('Could not read the import file.');
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Could not read the import file.');
        }

        $counts = [
            'imported' => 0,
            'already_suppressed' => 0,
            'skipped_blames_our_server' => 0,
            'skipped_no_reply' => 0,
            'skipped_already_lifted' => 0,
            'invalid' => 0,
        ];

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($row === [null]) {
                // A blank line.
                continue;
            }
            $address = EmailSuppressions::normalise((string) ($row[0] ?? ''));
            if ($address === 'address') {
                // The header row.
                continue;
            }
            if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
                $counts['invalid']++;
                continue;
            }

            $reply = trim((string) ($row[2] ?? ''));
            if ($reply === '') {
                // Without the receiving server's reply we can't tell a dead
                // address from a failure that was ours.
                $counts['skipped_no_reply']++;
                continue;
            }
            if (PostalWebhook::blamesOurServer($reply)) {
                $counts['skipped_blames_our_server']++;
                continue;
            }

            // Someone restored this address after the failure, by confirming a
            // link or through staff, so the old failure shouldn't stop their
            // alerts again. A failure after the lift is new and still counts.
            $failedAt = self::failureTime((string) ($row[1] ?? ''));
            if (EmailSuppressions::liftedSince($address, $failedAt)) {
                $counts['skipped_already_lifted']++;
                continue;
            }

            $suppressed = EmailSuppressions::suppress($address, self::REASON, null, $reply, $failedAt);
            $counts[$suppressed ? 'imported' : 'already_suppressed']++;
        }
        fclose($handle);

        return $counts;
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
