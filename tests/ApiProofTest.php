<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';
require_once __DIR__ . '/../www/includes/easyparliament/hansardlist.php';
require_once __DIR__ . '/../www/docs/api/api_functions.php';

/**
 * The API reports hansard.proof as true (proof, not final), false (final) or null (unknown).
 */
class ApiProofTest extends TestCase {

    /**
     *
     */
    public function test_proof_from_db_maps_each_state(): void {
        $this->assertTrue(HANSARDLIST::proof_from_db('1'));
        $this->assertTrue(HANSARDLIST::proof_from_db(1));
        $this->assertFalse(HANSARDLIST::proof_from_db('0'));
        $this->assertFalse(HANSARDLIST::proof_from_db(0));
        $this->assertNull(HANSARDLIST::proof_from_db(null));
        $this->assertNull(HANSARDLIST::proof_from_db(''));
    }

    /**
     *
     */
    public function test_xml_output_keeps_final_and_unknown_apart(): void {
        $this->assertSame('<proof>true</proof>', api_output_xml(['proof' => true]));
        $this->assertSame('<proof>false</proof>', api_output_xml(['proof' => false]));
        $this->assertSame('<proof></proof>', api_output_xml(['proof' => null]));
    }

    /**
     *
     */
    public function test_json_output_writes_real_booleans_and_null(): void {
        $this->assertSame('{"proof":true}', api_output_js(['proof' => true]));
        $this->assertSame('{"proof":false}', api_output_js(['proof' => false]));
        $this->assertSame('{"proof":null}', api_output_js(['proof' => null]));
    }

}
