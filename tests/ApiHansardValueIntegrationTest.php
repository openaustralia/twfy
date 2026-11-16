<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';
require_once __DIR__ . '/../www/includes/easyparliament/hansardlist.php';
require_once __DIR__ . '/../www/docs/api/api_functions.php';

use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;

/**
 * A row read from the database reaches the API with the same value types it always had:
 * the integer columns as strings, and proof as a real boolean.
 */
class ApiHansardValueIntegrationTest extends TransactionalTestCase {

    /**
     * Read one row back the way the API does.
     */
    private function itemFor(int $id): array {
        // The legacy list code reads these globals and the shared mysqli connection, so set up both.
        global $hansardmajors;
        include __DIR__ . '/../www/includes/dbtypes.php';

        Hansard::create([
            'epobject_id' => $id,
            'gid' => "uk.org.publicwhip/debate/2026-03-06.$id.1",
            'htype' => 12,
            'major' => 1,
            'section_id' => 900100,
            'subsection_id' => 900101,
            'hpos' => 5,
            'hdate' => '2026-03-06',
            'proof' => false,
        ]);

        $rows = (new DEBATELIST())->_get_hansard_data([
            'amount' => [],
            'where' => ['hansard.epobject_id=' => (string) $id],
        ]);

        return $rows[0];
    }

    /**
     *
     */
    public function test_hansard_data_returns_integer_columns_as_strings(): void {
        $item = $this->itemFor(900110);

        $this->assertSame('900110', $item['epobject_id']);
        $this->assertSame('12', $item['htype']);
        $this->assertSame('1', $item['major']);
        $this->assertSame('900100', $item['section_id']);
        $this->assertSame('900101', $item['subsection_id']);
        $this->assertSame('5', $item['hpos']);
    }

    /**
     *
     */
    public function test_json_output_quotes_the_integer_columns_and_not_proof(): void {
        $json = api_output_js($this->itemFor(900111));

        $this->assertStringContainsString('"hpos":"5"', $json);
        $this->assertStringContainsString('"major":"1"', $json);
        $this->assertStringContainsString('"proof":false', $json);
    }

    /**
     * Store one speech for a day, at a position in that day's debate.
     */
    private function makeRow(int $id, int $hpos): void {
        Hansard::create([
            'epobject_id' => $id,
            'gid' => "uk.org.publicwhip/debate/2026-03-07.$id.1",
            'htype' => 12,
            'major' => 1,
            'section_id' => 900100,
            'subsection_id' => 900101,
            'hpos' => $hpos,
            'hdate' => '2026-03-07',
        ]);
    }

    /**
     * Run _get_hansard_data() for the stored day, with extra options.
     */
    private function dayData(array $options): array {
        global $hansardmajors;
        include __DIR__ . '/../www/includes/dbtypes.php';

        return (new DEBATELIST())->_get_hansard_data($options + [
            'amount' => [],
            'where' => ['hansard.hdate=' => '2026-03-07'],
        ]);
    }

    /**
     *
     */
    public function test_order_and_limit_choose_which_rows_come_back(): void {
        foreach ([900120 => 1, 900121 => 2, 900122 => 3] as $id => $hpos) {
            $this->makeRow($id, $hpos);
        }

        $rows = $this->dayData(['order' => 'hansard.hpos DESC', 'limit' => '2']);

        $this->assertSame(['3', '2'], array_column($rows, 'hpos'));
    }

    /**
     *
     */
    public function test_a_limit_with_an_offset_skips_rows(): void {
        foreach ([900123 => 1, 900124 => 2, 900125 => 3] as $id => $hpos) {
            $this->makeRow($id, $hpos);
        }

        $rows = $this->dayData(['order' => 'hansard.hpos ASC', 'limit' => '1,1']);

        $this->assertSame(['2'], array_column($rows, 'hpos'));
    }

    /**
     *
     */
    public function test_an_unsupported_where_clause_is_rejected(): void {
        $this->makeRow(900126, 1);

        $this->expectException(InvalidArgumentException::class);

        $this->dayData(['where' => ['hansard.hpos BETWEEN' => '1']]);
    }

    /**
     *
     */
    public function test_the_body_is_joined_in_when_asked_for(): void {
        $this->makeRow(900127, 1);
        Epobject::create(['epobject_id' => 900127, 'body' => '<p>The budget was introduced.</p>', 'type' => 1]);

        $rows = $this->dayData(['amount' => ['body' => true]]);

        $this->assertStringContainsString('The budget was introduced.', $rows[0]['body']);
    }

}
