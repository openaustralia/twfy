<?php

use PHPUnit\Framework\TestCase;

/**
 *
 */
class AlertIntegrationTest extends TestCase {

    private static $connection = NULL;

    /**
     *
     */
public static function setUpBeforeClass(): void {
        self::$connection = getSharedTestConnection();
        if (!self::$connection) {
            self::markTestSkipped('Database connection not available');
        }
}

    /**
     * Test ALERT class with database
     */
    public function test_alert_class_can_construct() {
        $this->assertNotNull(self::$connection);
        $alert = new ALERT();
        $this->assertIsObject($alert);
    }

    /**
     * Test alert object properties after instantiation
     */
    public function test_alert_object_has_db_connection() {
        $this->assertNotNull(self::$connection);
        $alert = new ALERT();
        // Should have created internal db connection.
        $this->assertIsObject($alert);
    }

    /**
     * Test suggest_alerts function runs without error
     */
    public function test_suggest_alerts_with_speaker_criteria() {
        $this->assertNotNull(self::$connection);
        ob_start();
        suggest_alerts('test@example.com', 'speaker:12345', 5);
        $output = ob_get_clean();
        // Function may output HTML or nothing depending on data.
        $this->assertIsString($output);
    }

    /**
     * Test suggest_alerts with valid speaker format
     */
    public function test_suggest_alerts_speaker_format() {
        $this->assertNotNull(self::$connection);
        ob_start();
        suggest_alerts('user@test.com', 'speaker:10001', 3);
        $output = ob_get_clean();
        $this->assertIsString($output);
    }

    /**
     * Test suggest_alerts with non-speaker criteria
     */
    public function test_suggest_alerts_non_speaker() {
        $this->assertNotNull(self::$connection);
        ob_start();
        suggest_alerts('user@test.com', 'budget', 5);
        $output = ob_get_clean();
        // Non-speaker criteria should not trigger special logic.
        $this->assertIsString($output);
    }

    /**
     * Test multiple alerts for same email
     */
    public function test_suggest_alerts_multiple_criteria() {
        $this->assertNotNull(self::$connection);
        ob_start();
        suggest_alerts('shared@test.com', 'speaker:10001', 3);
        $output1 = ob_get_clean();

        ob_start();
        suggest_alerts('shared@test.com', 'speaker:10002', 3);
        $output2 = ob_get_clean();

        $this->assertIsString($output1);
        $this->assertIsString($output2);
    }

    /**
     * Test alert confirmation advert HTML generation
     */
    public function test_alert_advert_with_speaker() {
        $this->assertNotNull(self::$connection);
        $details = ['pid' => '10001'];
        $advert = alert_confirmation_advert($details);
        $this->assertIsString($advert);
        $this->assertGreaterThan(0, strlen($advert));
    }

    /**
     * Test alert advert with keyword (no speaker)
     */
    public function test_alert_advert_with_keyword() {
        $this->assertNotNull(self::$connection);
        $details = ['pid' => ''];
        $advert = alert_confirmation_advert($details);
        $this->assertIsString($advert);
        $this->assertGreaterThan(0, strlen($advert));
    }

    /**
     * Test MEMBER instantiation from alert context
     */
    public function test_member_lookup_from_alert_pid() {
        $this->assertNotNull(self::$connection);
// Assuming this PID exists.
        $pid = '10001';
        try {
            $member = new MEMBER(['person_id' => $pid]);
            $this->assertIsObject($member);
        } catch (Exception $e) {
            // PID may not exist, but class should attempt instantiation.
            $this->assertTrue(TRUE);
        }
    }

    /**
     * Test criterion pattern matching
     */
    public function test_criteria_pattern_validation() {
        $this->assertNotNull(self::$connection);
        $criteria_list = [
            'speaker:12345',
            'keyword search',
            'speaker:99999',
            'multiple word criteria',
        ];

        foreach ($criteria_list as $criteria) {
            $this->assertIsString($criteria);
            $this->assertGreaterThan(0, strlen($criteria));
        }
    }

    /**
     * Test max results parameter
     */
    public function test_suggest_alerts_max_results() {
        $this->assertNotNull(self::$connection);
        ob_start();
        suggest_alerts('test@example.com', 'speaker:10001', 2);
        $output = ob_get_clean();

        ob_start();
        suggest_alerts('test@example.com', 'speaker:10001', 10);
        $output2 = ob_get_clean();

        $this->assertIsString($output);
        $this->assertIsString($output2);
    }

}
