<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/member.php";

use OpenAustralia\TWFY\AlertStats;

$this_page = "alert_stats";

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'alerts', 'title' => 'Alert Statistics']);

print '<h3>People who probably wanted separate signups</h3>';
print_alert_stats_table([], array_map(
    fn ($alert) => [$alert->alert_id, $alert->criteria],
    AlertStats::probablySeparateSignups()->all()
));

print '<h3>Alert signups by MP</h3>';
$by_speaker = AlertStats::bySpeaker(function (int $person_id): ?string {
    $MEMBER = new MEMBER(['person_id' => $person_id]);
    return $MEMBER->valid ? $MEMBER->full_name() : null;
});
print_alert_stats_table(['Name', 'Confirmed', 'Unconfirmed'], array_map(
    fn ($row) => [$row['name'], $row['confirmed'], $row['unconfirmed']],
    $by_speaker
));

print '<h3>Alert signups by keyword</h3>';
print_alert_stats_table(['Criteria', 'Confirmed', 'Unconfirmed'], array_map(
    fn ($row) => [$row['criteria'], $row['confirmed'], $row['unconfirmed']],
    AlertStats::byKeyword()
));

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();

/**
 * Prints a table, escaping every cell.
 *
 * @param list<string> $header
 *   The column headings, or none for a table without a heading row.
 * @param list<list<string|int>> $rows
 *   The cells of each row.
 */
function print_alert_stats_table(array $header, array $rows): void {
    print '<table>';
    if ($header) {
        print '<tr><th>' . implode('</th><th>', array_map('htmlspecialchars', $header)) . '</th></tr>';
    }
    foreach ($rows as $row) {
        print '<tr><td>' . implode('</td><td>', array_map(fn ($cell) => htmlspecialchars((string) $cell), $row)) . '</td></tr>';
    }
    print '</table>';
}
