<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';
require_once __DIR__ . '/../www/includes/easyparliament/hansardlist.php';
require_once __DIR__ . '/../www/docs/api/api_functions.php';

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

}
