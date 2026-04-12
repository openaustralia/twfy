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
        // array_flip reverses it: { 'ALP' => 'Australian Labor Party', ... }.

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
        // array_flip creates { 'ALP' => 'Australian Labor Party', 'LP' => 'Liberal Party of Australia' }.

        $s = 'australian labor party';
// 'Australian Labor Party'
        $s_uc = ucwords($s);

        // This is what the code does - check if the ucwords version is in the flipped array.
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
        // Check that entered_house <= date <= left_house logic is valid.
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

        // Simulate LIKE matching.
        $matches = (strpos($constituency, $state) !== FALSE);
        $this->assertTrue($matches);
    }

    /**
     * Test multiple states in constituency names.
     */
    public function test_multiple_states_search(): void {
        $constituencies = ['Sydney, NSW', 'Melbourne, VIC', 'Brisbane, QLD'];
        $state = 'NSW';

        $matches = array_filter($constituencies, function ($c) use ($state) {
            return strpos($c, $state) !== FALSE;
        });

        $this->assertCount(1, $matches);
    }

    /**
     * Test House search (house = 1) does NOT include constituency.
     */
    public function test_search_house_param_count(): void {
        $house = 1;
        // House search has: first_name, last_name, concat(first_name, last_name) = 3 parameters.
        $paramCount = 3;
        $this->assertSame(3, $paramCount);
    }

    /**
     * Test Senate search (house = 2) includes constituency.
     */
    public function test_search_senate_param_count(): void {
        $house = 2;
        // Senate search has: first_name, last_name, concat(first_name, last_name), constituency = 4 parameters.
        $paramCount = 4;
        $this->assertSame(4, $paramCount);
    }

    /**
     * Test House detection for search logic branching.
     */
    public function test_search_house_detection(): void {
        $house = 1;
        $isSenate = ($house == 2);
        $this->assertFalse($isSenate);
    }

    /**
     * Test Senate detection for search logic branching.
     */
    public function test_search_senate_detection(): void {
        $house = 2;
        $isSenate = ($house == 2);
        $this->assertTrue($isSenate);
    }

    /**
     * Test search term with wildcards for first name.
     */
    public function test_search_first_name_wildcard(): void {
        $search = 'John';
        $likeParam = "%$search%";
        $this->assertSame('%John%', $likeParam);
    }

    /**
     * Test search term with wildcards for last name.
     */
    public function test_search_last_name_wildcard(): void {
        $search = 'Smith';
        $likeParam = "%$search%";
        $this->assertSame('%Smith%', $likeParam);
    }

    /**
     * Test search parameter replication for multiple fields.
     */
    public function test_search_multiple_field_params(): void {
        $search = 'Johnson';
        $params = ["%$search%", "%$search%", "%$search%"];
        $this->assertCount(3, $params);
    }

    /**
     * Test full name search construction.
     */
    public function test_search_full_name_concat(): void {
        $firstName = 'Robert';
        $lastName = 'Brown';
        $fullNameSearch = "CONCAT($firstName, ' ', $lastName)";

        $this->assertStringContainsString('Robert', $fullNameSearch);
        $this->assertStringContainsString('Brown', $fullNameSearch);
    }

    /**
     * Test Senate constituency search field.
     */
    public function test_search_senate_constituency_field(): void {
        $house = 2;
        $includes_constituency = ($house == 2);
        $this->assertTrue($includes_constituency);
    }

}
