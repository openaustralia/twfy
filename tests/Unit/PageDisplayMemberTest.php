<?php

use PHPUnit\Framework\TestCase;

if (!defined('SHORTDATEFORMAT')) {
    define('SHORTDATEFORMAT', 'j M Y');
}

if (!defined('REGMEMPDFPATH')) {
    define('REGMEMPDFPATH', 'regmem/scan/');
}

// display_member() calls this in the senate-only recent appearances branch.
// Define a no-op stub so this unit test can run without the full app debug stack.
if (!function_exists('twfy_debug_timestamp')) {

    function twfy_debug_timestamp() {
    }

}

// display_member() instantiates SEARCHENGINE in the same branch.
// This lightweight stub keeps the test focused on PAGE output behavior.
if (!class_exists('SEARCHENGINE')) {

    class SEARCHENGINE {

        public function __construct($search) {
        }

    }

}

// display_member() also instantiates HANSARDLIST and calls display().
// Stub it so we can assert that the branch executed without requiring search/index dependencies.
if (!class_exists('HANSARDLIST')) {

    class HANSARDLIST {

        public function display($view, $args) {
            echo '<div class="hansardlist-test-stub"></div>';
        }

    }

}

require_once INCLUDESPATH . 'url.php';
require_once BASEDIR . '/includes/easyparliament/page.php';

class TestablePageForDisplayMember extends PAGE {

    public function block_start($data = []) {
        echo '<div class="block-test">';
        if (isset($data['title'])) {
            echo $data['title'];
        }
    }

    public function block_end() {
        echo '</div>';
    }

}

class FakeDataForDisplayMember {

    public function __construct(private string $rss = '') {
    }

    public function page_metadata($page, $key) {
        if ($key === 'rss') {
            return $this->rss;
        }
        if ($key === 'session_vars') {
            return [];
        }
        return '';
    }

}

class FakeMemberForLinks {

    public function __construct(
        private string $fullName,
        private string $firstName,
        private string $lastName,
        private string $constituency,
        private int $memberId
    ) {
    }

    public function full_name(): string {
        return $this->fullName;
    }

    public function first_name(): string {
        return $this->firstName;
    }

    public function last_name(): string {
        return $this->lastName;
    }

    public function constituency(): string {
        return $this->constituency;
    }

    public function member_id(): int {
        return $this->memberId;
    }

}

class PageDisplayMemberTest extends TestCase {

    private mixed $originalData;
    private mixed $originalThisPage;
    private mixed $originalTheUser;
    private array $originalServer;

    protected function setUp(): void {
        global $DATA, $this_page, $THEUSER;

        $this->originalData = $DATA ?? null;
        $this->originalThisPage = $this_page ?? null;
        $this->originalTheUser = $THEUSER ?? null;
        $this->originalServer = $_SERVER;
        $_GET = [];
    }

    protected function tearDown(): void {
        global $DATA, $this_page, $THEUSER;

        $DATA = $this->originalData;
        $this_page = $this->originalThisPage;
        $THEUSER = $this->originalTheUser;
        $_SERVER = $this->originalServer;
    }

    public function test_display_member_renders_former_representative_summary(): void {
        global $DATA, $this_page, $THEUSER;

        $DATA = new FakeDataForDisplayMember('');
        $this_page = 'mp';
        $THEUSER = new stdClass();
        unset($_SERVER['DEVICE_TYPE']);

        $member = [
            'person_id' => 100001,
            'member_id' => 200001,
            'full_name' => 'Alex Warringah',
            'house_disp' => HOUSE::REPRESENTATIVES,
            'houses' => [HOUSE::REPRESENTATIVES],
            'current_member' => [
                HOUSE::REPRESENTATIVES => false,
                HOUSE::SENATE => false,
            ],
            'left_house' => [
                HOUSE::REPRESENTATIVES => [
                    'party' => 'Independent',
                    'constituency' => 'Warringah',
                    'date_pretty' => '21 May 2022',
                    'reason' => 'Defeated',
                ],
            ],
            'entered_house' => [],
            'other_parties' => [],
            'party' => 'Independent',
        ];

        $page = new TestablePageForDisplayMember();

        ob_start();
        $page->display_member($member, []);
        $html = ob_get_clean();

        $this->assertIsString($html);
        $this->assertStringContainsString('former Representative', $html);
        $this->assertStringContainsString('Left House of Representatives on 21 May 2022', $html);
        $this->assertStringNotContainsString('Email me whenever Alex Warringah speaks', $html);
    }

    public function test_display_member_renders_current_member_alert_and_ministerial_office(): void {
        global $DATA, $this_page, $THEUSER;

        $DATA = new FakeDataForDisplayMember('rss/mp.xml');
        $this_page = 'mp';
        $THEUSER = new stdClass();
        $_SERVER['DEVICE_TYPE'] = 'desktop';

        $member = [
            'person_id' => 100002,
            'member_id' => 200002,
            'full_name' => 'Pat Canberra',
            'house_disp' => HOUSE::REPRESENTATIVES,
            'houses' => [HOUSE::REPRESENTATIVES],
            'current_member' => [
                HOUSE::REPRESENTATIVES => true,
                HOUSE::SENATE => false,
            ],
            'left_house' => [
                HOUSE::REPRESENTATIVES => [
                    'party' => 'ALP',
                    'constituency' => 'Canberra',
                    'date_pretty' => 'Present',
                    'reason' => '',
                ],
            ],
            'entered_house' => [
                HOUSE::REPRESENTATIVES => [
                    'date' => '2022-05-21',
                    'date_pretty' => '21 May 2022',
                    'reason' => 'General election',
                ],
            ],
            'other_parties' => [],
            'party' => 'ALP',
        ];

        $extraInfo = [
            'office' => [
                [
                    'position' => 'Minister for Finance',
                    'dept' => 'Finance',
                    'from_date' => '2022-06-01',
                    'to_date' => '9999-12-31',
                    'source' => 'cabinet',
                ],
                [
                    'position' => 'Chair',
                    'dept' => 'Economics',
                    'from_date' => '2022-06-01',
                    'to_date' => '9999-12-31',
                    'source' => 'chgpages/selctee',
                ],
            ],
        ];

        $page = new TestablePageForDisplayMember();

        ob_start();
        $page->display_member($member, $extraInfo);
        $html = ob_get_clean();

        $this->assertIsString($html);
        $this->assertStringContainsString('Pat Canberra', $html);
        $this->assertStringContainsString('/rss/mp.xml', $html);
        $this->assertStringContainsString('Email me whenever Pat Canberra speaks', $html);
        $this->assertStringContainsString('Minister for Finance', $html);
        $this->assertStringNotContainsString('Chair, Economics (since', $html);
    }

    public function test_display_member_renders_current_senator_title_and_membership(): void {
        global $DATA, $this_page, $THEUSER;

        $DATA = new FakeDataForDisplayMember('');
        $this_page = 'mp';
        $THEUSER = new stdClass();
        $_SERVER['DEVICE_TYPE'] = 'desktop';

        $member = [
            'person_id' => 100003,
            'member_id' => 200003,
            'full_name' => 'Jordan Victorian',
            'house_disp' => HOUSE::SENATE,
            'houses' => [HOUSE::SENATE, HOUSE::REPRESENTATIVES],
            'current_member' => [
                HOUSE::REPRESENTATIVES => false,
                HOUSE::SENATE => true,
            ],
            'left_house' => [
                HOUSE::REPRESENTATIVES => [
                    'party' => 'ALP',
                    'constituency' => 'Canberra',
                    'date_pretty' => '21 May 2022',
                    'reason' => 'Resigned',
                ],
                HOUSE::SENATE => [
                    'party' => 'GRN',
                    'constituency' => 'Victoria',
                    'date_pretty' => 'Present',
                    'reason' => '',
                ],
            ],
            'entered_house' => [
                HOUSE::SENATE => [
                    'date' => '2022-07-01',
                    'date_pretty' => '1 Jul 2022',
                    'reason' => 'General election',
                ],
            ],
            'other_parties' => [],
            'party' => 'GRN',
        ];

        $page = new TestablePageForDisplayMember();

        ob_start();
        $page->display_member($member, []);
        $html = ob_get_clean();

        $this->assertIsString($html);
        $this->assertStringContainsString('Senator Jordan Victorian', $html);
        $this->assertStringContainsString('GRN Senator for Victoria', $html);
        $this->assertStringContainsString('Entered the Senate on 1 Jul 2022', $html);
        $this->assertStringContainsString('former Representative', $html);
        $this->assertStringContainsString('Email me whenever Jordan Victorian speaks', $html);
    }

    public function test_display_member_senate_only_member_renders_recent_appearances_block(): void {
        global $DATA, $this_page, $THEUSER;

        $DATA = new FakeDataForDisplayMember('rss/senate.xml');
        $this_page = 'mp';
        $THEUSER = new stdClass();
        $_SERVER['DEVICE_TYPE'] = 'desktop';

        $member = [
            'person_id' => 100004,
            'member_id' => 200004,
            'full_name' => 'Taylor Tasman',
            'house_disp' => HOUSE::SENATE,
            'houses' => [HOUSE::SENATE],
            'current_member' => [
                HOUSE::REPRESENTATIVES => false,
                HOUSE::SENATE => true,
            ],
            'left_house' => [
                HOUSE::SENATE => [
                    'party' => 'IND',
                    'constituency' => 'Tasmania',
                    'date_pretty' => 'Present',
                    'reason' => '',
                ],
            ],
            'entered_house' => [
                HOUSE::SENATE => [
                    'date' => '2019-07-01',
                    'date_pretty' => '1 Jul 2019',
                    'reason' => 'General election',
                ],
            ],
            'other_parties' => [],
            'party' => 'IND',
        ];

        $page = new TestablePageForDisplayMember();

        ob_start();
        $page->display_member($member, []);
        $html = ob_get_clean();

        $this->assertIsString($html);
        $this->assertStringContainsString('Most recent appearances in parliament', $html);
        $this->assertStringContainsString('Taylor Tasman\'s recent appearances', $html);
        $this->assertStringContainsString('hansardlist-test-stub', $html);
    }

    public function test_generate_member_links_renders_email_and_maiden_speech_links(): void {
        $page = new TestablePageForDisplayMember();
        $member = new FakeMemberForLinks('Pat Canberra', 'Pat', 'Canberra', 'Canberra', 200002);

        $links = [
            'maiden_speech' => 'uk.org.publicwhip/debate/2024-01-01a.1.0',
            'mp_email' => 'pat@example.org.au',
            'mp_twitter_url' => 'https://twitter.com/patcanberra',
            'mp_website' => 'https://pat.example.org.au',
            'aph_url' => 'https://www.aph.gov.au/pat',
        ];

        $html = $page->generate_member_links($member, $links);

        $this->assertIsString($html);
        $this->assertStringContainsString('More useful links for this person', $html);
        $this->assertStringContainsString('Maiden speech', $html);
        $this->assertStringContainsString('mailto:pat@example.org.au', $html);
        $this->assertStringContainsString('Pat Canberra on Twitter', $html);
        $this->assertStringContainsString('Pat Canberra\'s personal website', $html);
        $this->assertStringContainsString('Parliament House web page for Pat Canberra', $html);
        $this->assertStringNotContainsString('Contact form', $html);
    }

    public function test_generate_member_links_renders_contact_form_and_c4_list_style(): void {
        $_GET['c4'] = '1';

        $page = new TestablePageForDisplayMember();
        $member = new FakeMemberForLinks('Taylor Tasman', 'Taylor', 'Tasman', 'Tasmania', 200004);

        $links = [
            'mp_contact_form' => 'https://www.aph.gov.au/contact/taylor',
            'abc_election_results_2022' => 'https://www.abc.net.au/elections/federal/2022/guide/tasm',
        ];

        $html = $page->generate_member_links($member, $links);

        $this->assertIsString($html);
        $this->assertStringContainsString('style="list-style-type:none;"', $html);
        $this->assertStringContainsString('Contact form', $html);
        $this->assertStringContainsString('On the Australian Parliament website', $html);
        $this->assertStringContainsString('2022 Election results for Tasmania', $html);
        $this->assertStringNotContainsString('mailto:', $html);
    }

}
