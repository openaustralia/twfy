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
 * The search loader reads its rows from the database and the matches from Xapian. A stand-in for the search engine
 * lets these tests check the rows without Xapian, so search results carry the same value types as every other API result.
 */
class HansardSearchLoaderIntegrationTest extends TransactionalTestCase {

    /**
     * Whether $SEARCHENGINE was set before the test, so tearDown() can put it back as it found it.
     */
    private bool $hadSearchEngine = false;

    /**
     * The global $SEARCHENGINE before the test, if there was one.
     */
    private mixed $previousSearchEngine = null;

    /**
     *
     */
    protected function setUp(): void {
        parent::setUp();
        $this->hadSearchEngine = array_key_exists('SEARCHENGINE', $GLOBALS);
        $this->previousSearchEngine = $GLOBALS['SEARCHENGINE'] ?? null;
    }

    /**
     * Other tests share this global, so put it back the way it was.
     */
    protected function tearDown(): void {
        if ($this->hadSearchEngine) {
            $GLOBALS['SEARCHENGINE'] = $this->previousSearchEngine;
        } else {
            unset($GLOBALS['SEARCHENGINE']);
        }
        parent::tearDown();
    }

    /**
     * Search the loader for one stored item, with a search engine that returns just that item.
     */
    private function search(int $id, int $htype): array {
        // The legacy list code reads these globals and the shared mysqli connection, so set up both.
        global $hansardmajors, $SEARCHENGINE;
        include __DIR__ . '/../www/includes/dbtypes.php';

        $gid = "uk.org.publicwhip/debate/2026-03-08.$id.1";
        Epobject::create(['epobject_id' => $id, 'body' => '<p>The budget was introduced.</p>', 'type' => 1]);
        Hansard::create([
            'epobject_id' => $id,
            'gid' => $gid,
            'htype' => $htype,
            'major' => 1,
            'section_id' => 900200,
            'subsection_id' => 900201,
            'hpos' => 5,
            'hdate' => '2026-03-08',
            'proof' => true,
        ]);

        $SEARCHENGINE = new class($gid) {

            /**
             *
             */
            public function __construct(private string $gid) {
            }

            /**
             *
             */
            public function query_description_long(): string {
                return 'containing the word budget';
            }

            /**
             *
             */
            public function run_count(): int {
                return 1;
            }

            /**
             *
             */
            public function run_search(int $first_result, int $results_per_page, string $sort_order): void {
            }

            /**
             *
             */
            public function get_gids(): array {
                return [$this->gid];
            }

            /**
             *
             */
            public function get_relevances(): array {
                return [88];
            }

            /**
             *
             */
            public function position_of_first_word(string $text): int {
                return 0;
            }

            /**
             *
             */
            public function highlight(string $text): string {
                return $text;
            }

        };

        // 'pop' stops the loader logging the query.
        return (new DEBATELIST())->_get_data_by_search(['s' => 'budget', 'pop' => 1]);
    }

    /**
     *
     */
    public function test_search_results_return_integer_columns_as_strings(): void {
        $row = $this->search(900210, 10)['rows'][0];

        $this->assertSame('10', $row['htype']);
        $this->assertSame('1', $row['major']);
        $this->assertSame('900200', $row['section_id']);
        $this->assertSame('900201', $row['subsection_id']);
        $this->assertSame('0', $row['speaker_id']);
        $this->assertSame('5', $row['hpos']);
    }

    /**
     *
     */
    public function test_search_results_carry_proof_as_a_boolean(): void {
        $this->assertTrue($this->search(900211, 10)['rows'][0]['proof']);
    }

    /**
     *
     */
    public function test_search_results_report_the_match_and_the_totals(): void {
        $data = $this->search(900212, 10);

        $this->assertSame(1, $data['info']['total_results']);
        $this->assertSame('containing the word budget', $data['searchdescription']);
        $this->assertSame('2026-03-08.900212.1', $data['rows'][0]['gid']);
        $this->assertSame(88, $data['rows'][0]['relevance']);
    }

    /**
     *
     */
    public function test_a_section_is_its_own_parent_heading(): void {
        $row = $this->search(900213, 10)['rows'][0];

        $this->assertSame('', $row['body']);
        $this->assertStringContainsString('The budget was introduced.', $row['parent']['body']);
    }

    /**
     *
     */
    public function test_json_output_quotes_the_integer_columns_for_search_results(): void {
        $json = api_output_js($this->search(900214, 10)['rows'][0]);

        $this->assertStringContainsString('"hpos":"5"', $json);
        $this->assertStringContainsString('"htype":"10"', $json);
        $this->assertStringContainsString('"proof":true', $json);
    }

}
