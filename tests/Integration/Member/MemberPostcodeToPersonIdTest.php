<?php

/**
 * @file
 * Tests for MEMBER::postcode_to_person_id().
 */

require_once __DIR__ . '/../../bootstrap.php';

if (!function_exists('twfy_debug_timestamp')) {

    /**
     * Test stub for postcode helper timing debug hook.
     */
    function twfy_debug_timestamp(): void {
    }

}

if (!function_exists('validate_postcode')) {

    /**
     * Test stub matching postcode validation contract used by postcode.php.
     */
    function validate_postcode($postcode): int {
        return preg_match('/^[A-Z]{1,2}\d[A-Z\d]? ?\d[ABD-HJLNP-UW-Z]{2}$/i', (string) $postcode);
    }

}

/**
 * MEMBER double to observe postcode-to-constituency delegation.
 */
class MemberPostcodeMemberDouble extends MEMBER {

    public array $seenConstituencies = [];

    /**
     * Avoid running parent constructor DB logic in unit-style tests.
     */
    public function __construct() {
    }

    /**
     * Capture the value postcode_to_person_id delegates to.
     */
    public function constituency_to_person_id($constituency) {
        $this->seenConstituencies[] = $constituency;
        if ($constituency === '') {
            return false;
        }
        return 12345;
    }

}

/**
 * Tests for MEMBER::postcode_to_person_id().
 */
class MemberPostcodeToPersonIdTest extends TransactionalTestCase {

    /**
     * Create a MEMBER test double without running the heavy constructor.
     */
    private function makeMemberDouble(): MEMBER {
        return new MemberPostcodeMemberDouble();
    }

    /**
     * Method should lowercase postcode-derived constituency before delegating.
     */
    public function test_postcode_to_person_id_lowercases_constituency_before_lookup(): void {
        $postcode = 'ZZ1 1ZZ';
        parlDBQuery('DELETE FROM postcode_lookup WHERE postcode = ?', $postcode);
        parlDBQuery(
            'INSERT INTO postcode_lookup (postcode, name) VALUES (?, ?)',
            $postcode,
            'MiXeD Case Constituency'
        );

        $GLOBALS['last_postcode'] = null;
        $GLOBALS['last_postcode_value'] = null;

        $member = $this->makeMemberDouble();

        $result = $member->postcode_to_person_id('zz11zz');

        $this->assertSame(12345, $result);
        $this->assertSame(['mixed case constituency'], $member->seenConstituencies);
    }

    /**
     * Invalid postcode should delegate an empty constituency and return false.
     */
    public function test_postcode_to_person_id_passes_empty_string_for_invalid_postcode(): void {
        $member = $this->makeMemberDouble();

        $result = $member->postcode_to_person_id('not-a-postcode');

        $this->assertFalse($result);
        $this->assertSame([''], $member->seenConstituencies);
    }

}
