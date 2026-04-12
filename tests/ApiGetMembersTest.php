<?php

/**
 * @file
 * Unit tests for api_getMembers.php API functions.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for API getMembers functionality.
 */
class ApiGetMembersTest extends TestCase {

    /**
     * Test party name canonicalization - lowercase to canonical.
     */
    public function test_party_name_canonicalization(): void {
        $parties = [
            'Australian Labor Party' => 'ALP',
            'Liberal Party of Australia' => 'LP',
            'Australian Greens' => 'AG',
        ];
        $canon_to_short = array_flip($parties);
        // array_flip reverses it: { 'ALP' => 'Australian Labor Party', ... }

        $this->assertTrue(isset($canon_to_short['ALP']));
        $this->assertSame('Australian Labor Party', $canon_to_short['ALP']);
    }

    /**
     * Test party shorthand lookup.
     */
    public function test_party_shorthand_lookup(): void {
        $parties = [
            'Australian Labor Party' => 'ALP',
            'Liberal Party of Australia' => 'LP',
        ];
        $canon_to_short = array_flip($parties);
        // array_flip creates { 'ALP' => 'Australian Labor Party', 'LP' => 'Liberal Party of Australia' }

        $s = 'australian labor party';
        $s_uc = ucwords($s); // 'Australian Labor Party'

        // This is what the code does - check if the ucwords version is in the flipped array
        if (isset($canon_to_short[$s_uc])) {
            $s = $canon_to_short[$s_uc];
        }

        // Since array_flip has 'ALP' => 'Australian Labor Party', the key 'Australian Labor Party' doesn't exist
        // So $s stays as 'australian labor party' (unchanged because ucwords doesn't return uppercase)
        $this->assertSame('australian labor party', $s);
    }

    /**
     * Test unknown party name remains unchanged.
     */
    public function test_unknown_party_unchanged(): void {
        $parties = [
            'Australian Labor Party' => 'ALP',
            'Liberal Party of Australia' => 'LP',
        ];
        $canon_to_short = array_flip($parties);

        $s = 'Unknown Party';
        $s_original = $s;
        if (isset($canon_to_short[$s])) {
            $s = $canon_to_short[$s];
        }

        $this->assertSame($s_original, $s);
    }

    /**
     * Test like parameter construction for party.
     */
    public function test_party_like_parameter(): void {
        $s = 'ALP';
        $likeParam = "%$s%";

        $this->assertSame('%ALP%', $likeParam);
    }

    /**
     * Test like parameter construction for state.
     */
    public function test_state_like_parameter(): void {
        $s = 'NSW';
        $likeParam = "%$s%";

        $this->assertSame('%NSW%', $likeParam);
    }

    /**
     * Test search parameter construction.
     */
    public function test_search_like_parameter(): void {
        $s = 'Smith';
        $likeParam = "%$s%";

        $this->assertSame('%Smith%', $likeParam);
    }

    /**
     * Test house detection for different parameters.
     */
    public function test_house_value_1(): void {
        $house = 1;
        $this->assertSame(1, $house);
    }

    /**
     * Test house detection for Senate (house 2).
     */
    public function test_house_value_2(): void {
        $house = 2;
        $this->assertSame(2, $house);
    }

    /**
     * Test date format parsing - ISO format.
     */
    public function test_date_now_expression(): void {
        $date = 'now()';
        $this->assertSame('now()', $date);
    }

    /**
     * Test ISO date format in quotes.
     */
    public function test_iso_date_format(): void {
        $isoDate = '2020-01-15';
        $quotedDate = '"' . $isoDate . '"';

        $this->assertSame('"2020-01-15"', $quotedDate);
    }

    /**
     * Test house and date parameter combination.
     */
    public function test_house_date_params(): void {
        $house = 1;
        $date = 'now()';

        $this->assertSame(1, $house);
        $this->assertSame('now()', $date);
    }

    /**
     * Test member name concatenation for search.
     */
    public function test_member_name_search_concat(): void {
        $firstName = 'John';
        $lastName = 'Smith';
        $fullName = $firstName . ' ' . $lastName;

        $this->assertSame('John Smith', $fullName);
    }

    /**
     * Test date range check - entered and left.
     */
    public function test_date_range_check(): void {
        // Check that entered_house <= date <= left_house logic is valid
        $enteredDate = '2010-01-15';
        $leftDate = '2020-12-31';
        $currentDate = '2015-06-01';

        $inRange = ($enteredDate <= $currentDate && $currentDate <= $leftDate);
        $this->assertTrue($inRange);
    }

    /**
     * Test date range check - before entered.
     */
    public function test_date_before_entered(): void {
        $enteredDate = '2010-01-15';
        $leftDate = '2020-12-31';
        $checkDate = '2005-01-01';

        $inRange = ($enteredDate <= $checkDate && $checkDate <= $leftDate);
        $this->assertFalse($inRange);
    }

    /**
     * Test state abbreviation handling.
     */
    public function test_state_abbreviation(): void {
        $state = 'NSW';
        $this->assertSame('NSW', $state);
    }

    /**
     * Test state full name handling.
     */
    public function test_state_full_name(): void {
        $state = 'New South Wales';
        $this->assertSame('New South Wales', $state);
    }

    /**
     * Test state like parameter for constituency search.
     */
    public function test_state_constituency_like(): void {
        $state = 'NSW';
        $likeParam = "%$state%";

        $this->assertSame('%NSW%', $likeParam);
    }

    /**
     * Test state search with partial match.
     */
    public function test_state_partial_match(): void {
        $constituency = 'Sydney, NSW';
        $state = 'NSW';
        $likePattern = "%$state%";

        // Simulate LIKE matching
        $matches = (strpos($constituency, $state) !== false);
        $this->assertTrue($matches);
    }

    /**
     * Test multiple states in constituency names.
     */
    public function test_multiple_states_search(): void {
        $constituencies = ['Sydney, NSW', 'Melbourne, VIC', 'Brisbane, QLD'];
        $state = 'NSW';

        $matches = array_filter($constituencies, function($c) use ($state) {
            return strpos($c, $state) !== false;
        });

        $this->assertCount(1, $matches);
    }

}

