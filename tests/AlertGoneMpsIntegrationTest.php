<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/DatabaseIntegrationTestCase.php';
require_once __DIR__ . '/../scripts/alertgonemps.php';

class AlertGoneMpsIntegrationTest extends DatabaseIntegrationTestCase
{
    protected function createTemporaryTables(): void
    {
        // Load the full schema using multi_query on the raw connection
        $schema = file_get_contents(__DIR__ . '/../db/schema.sql');
        $conn = $this->getConnection();
        if (!$conn->multi_query($schema)) {
            $this->fail('Failed to load schema: ' . $conn->error);
        }
        // Consume all results from multi_query
        while ($conn->next_result()) {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }

        // Wire up parlDBQuery to use our test DB
        $GLOBALS['parldb_override'] = $this->db;
    }

    protected function dropTemporaryTables(): void
    {
        $conn = $this->getConnection();
        $conn->query("DROP TABLE IF EXISTS member");
        $conn->query("DROP TABLE IF EXISTS users");
        unset($GLOBALS['parldb_override']);
    }

    private function getConnection(): mysqli
    {
        $prop = new ReflectionProperty(MySQL::class, 'conn');
        $prop->setAccessible(true);
        return $prop->getValue($this->db);
    }

    private int $nextMemberId = 1;

    private function insertMember(int $person_id, string $first_name, string $last_name, string $left_house = '9999-12-31'): void
    {
        $member_id = $this->nextMemberId++;
        $this->db->query(
            "INSERT INTO member (member_id, person_id, first_name, last_name, constituency, party, left_house) VALUES (?, ?, ?, ?, ?, ?, ?)",
            $member_id, $person_id, $first_name, $last_name, 'Test Electorate', 'Test Party', $left_house
        );
    }

    private function insertUser(string $email): int
    {
        $q = $this->db->query("INSERT INTO users (email) VALUES (?)", $email);
        return (int) $q->insert_id();
    }

    public function testGetMemberDepartureInfoReturnsNullForUnknownMember(): void
    {
        $result = get_member_departure_info(99999);
        $this->assertNull($result);
    }

    public function testGetMemberDepartureInfoReturnsDataForKnownMember(): void
    {
        $this->insertMember(100, 'Julia', 'Gillard', '2013-06-27');

        $result = get_member_departure_info(100);

        $this->assertNotNull($result);
        $this->assertEquals('2013-06-27', $result['left_house']);
        $this->assertEquals('Julia Gillard', $result['name']);
    }

    public function testGetMemberDepartureInfoReturnsMaxLeftHouse(): void
    {
        // Member with multiple terms - should return the most recent left_house
        $this->insertMember(200, 'Kevin', 'Rudd', '2010-06-24');
        $this->db->query(
            "INSERT INTO member (person_id, first_name, last_name, left_house) VALUES (?, ?, ?, ?)",
            200, 'Kevin', 'Rudd', '2013-09-07'
        );

        $result = get_member_departure_info(200);

        $this->assertEquals('2013-09-07', $result['left_house']);
    }

    public function testGetUserIdByEmailReturnsZeroForUnknown(): void
    {
        $result = get_user_id_by_email('nobody@example.com');
        $this->assertEquals(0, $result);
    }

    public function testGetUserIdByEmailReturnsIdForKnownUser(): void
    {
        $userId = $this->insertUser('voter@example.com');

        $result = get_user_id_by_email('voter@example.com');
        $this->assertEquals($userId, $result);
    }

    public function testFindGoneMpAlertsFiltersNonSpeakerAlerts(): void
    {
        $alerts = [
            ['email' => 'a@example.com', 'criteria' => 'keyword:climate'],
        ];

        $result = find_gone_mp_alerts($alerts);

        $this->assertEmpty($result['alerts']);
        $this->assertEquals(0, $result['mp_count']);
    }

    public function testFindGoneMpAlertsSkipsCurrentMembers(): void
    {
        $this->insertMember(300, 'Anthony', 'Albanese'); // still serving (default 9999-12-31)

        $alerts = [
            ['email' => 'a@example.com', 'criteria' => 'speaker:300'],
        ];

        $result = find_gone_mp_alerts($alerts);

        $this->assertEmpty($result['alerts']);
    }

    public function testFindGoneMpAlertsReturnsDepartedMembers(): void
    {
        $this->insertMember(400, 'Tony', 'Abbott', '2019-05-18');
        $this->insertUser('concerned@example.com');

        $alerts = [
            ['email' => 'concerned@example.com', 'criteria' => 'speaker:400'],
        ];

        $result = find_gone_mp_alerts($alerts);

        $this->assertCount(1, $result['alerts']);
        $this->assertEquals('Tony Abbott', $result['alerts'][0]['name']);
        $this->assertEquals(400, $result['alerts'][0]['person_id']);
        $this->assertEquals('concerned@example.com', $result['alerts'][0]['email']);
        $this->assertEquals(1, $result['mp_count']);
        $this->assertEquals(1, $result['registered']);
        $this->assertEquals(0, $result['unregistered']);
    }

    public function testFindGoneMpAlertsCountsUnregisteredUsers(): void
    {
        $this->insertMember(500, 'Malcolm', 'Turnbull', '2018-08-24');

        $alerts = [
            ['email' => 'noreg@example.com', 'criteria' => 'speaker:500'],
        ];

        $result = find_gone_mp_alerts($alerts);

        $this->assertCount(1, $result['alerts']);
        $this->assertEquals(0, $result['alerts'][0]['user_id']);
        $this->assertEquals(0, $result['registered']);
        $this->assertEquals(1, $result['unregistered']);
    }

    public function testFindGoneMpAlertsHandlesMultipleAlertsForSameEmail(): void
    {
        $this->insertMember(600, 'Bob', 'Hawke', '2010-01-01');
        $this->insertMember(601, 'Paul', 'Keating', '2012-01-01');

        $alerts = [
            ['email' => 'fan@example.com', 'criteria' => 'speaker:600'],
            ['email' => 'fan@example.com', 'criteria' => 'speaker:601'],
        ];

        $result = find_gone_mp_alerts($alerts);

        $this->assertCount(2, $result['alerts']);
        $this->assertEquals(2, $result['mp_count']);
        // Same email should only count once for registered/unregistered
        $this->assertEquals(0, $result['registered']);
        $this->assertEquals(1, $result['unregistered']);
    }

    public function testPrepareEmailBodyForRegisteredUser(): void
    {
        $body = prepare_email_body(1, 'Your MP has left.');
        $this->assertStringContainsString('As a registered user', $body);
        $this->assertStringContainsString('Your MP has left.', $body);
    }

    public function testPrepareEmailBodyForUnregisteredUser(): void
    {
        $body = prepare_email_body(0, 'Your MP has left.');
        $this->assertStringContainsString('If you register on the site', $body);
        $this->assertStringContainsString('Your MP has left.', $body);
    }
}
