<?php

/**
 * @file
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';
require_once __DIR__ . '/../www/includes/easyparliament/hansardlist.php';
require_once __DIR__ . '/../www/docs/api/api_functions.php';

/**
 * The API has always returned the hansard integer columns as strings, so reading them through Eloquent must not change that.
 */
class ApiHansardValueTest extends TestCase {

    /**
     * Each integer column the API returns, with an integer as Eloquent returns it.
     */
    public static function integerColumns(): array {
        return [
            'epobject_id' => ['epobject_id'],
            'htype' => ['htype'],
            'hpos' => ['hpos'],
            'section_id' => ['section_id'],
            'subsection_id' => ['subsection_id'],
            'major' => ['major'],
            'speaker_id' => ['speaker_id'],
        ];
    }

    /**
     * Every integer column is cast, not just a sample.
     */
    #[DataProvider('integerColumns')]
    public function test_integer_columns_are_cast_to_strings(string $column): void {
        $this->assertSame('221', HANSARDLIST::api_value($column, 221));
    }

    /**
     *
     */
    public function test_null_stays_null(): void {
        $this->assertNull(HANSARDLIST::api_value('speaker_id', null));
    }

    /**
     *
     */
    public function test_other_columns_are_left_alone(): void {
        $this->assertSame('2026-03-06', HANSARDLIST::api_value('hdate', '2026-03-06'));
        $this->assertSame('uk.org.publicwhip/debate/2026-03-06.1.1', HANSARDLIST::api_value('gid', 'uk.org.publicwhip/debate/2026-03-06.1.1'));
        $this->assertSame(1, HANSARDLIST::api_value('proof', 1));
    }

    /**
     *
     */
    public function test_json_output_quotes_the_integer_columns(): void {
        $item = ['hpos' => HANSARDLIST::api_value('hpos', 221), 'htype' => HANSARDLIST::api_value('htype', 12)];

        $this->assertSame('{"hpos":"221","htype":"12"}', api_output_js($item));
    }

}
