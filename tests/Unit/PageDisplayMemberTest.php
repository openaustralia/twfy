<?php

use PHPUnit\Framework\TestCase;

if (!defined('SHORTDATEFORMAT')) {
    define('SHORTDATEFORMAT', 'j M Y');
}

if (!defined('REGMEMPDFPATH')) {
    define('REGMEMPDFPATH', 'regmem/scan/');
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

}
