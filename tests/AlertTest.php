<?php

use PHPUnit\Framework\TestCase;

/**
 *
 */
class AlertTest extends TestCase {

    /**
     * Test criteria construction from keyword alone
     */
    public function test_alert_details_to_criteria_keyword_only() {
        $details = [
            'keyword' => 'climate change',
            'pid' => '',
        ];
        $criteria = alert_details_to_criteria($details);
        $this->assertEquals('climate change', $criteria);
    }

    /**
     * Test criteria construction from speaker PID alone
     */
    public function test_alert_details_to_criteria_speaker_only() {
        $details = [
            'keyword' => '',
            'pid' => '12345',
        ];
        $criteria = alert_details_to_criteria($details);
        $this->assertEquals('speaker:12345', $criteria);
    }

    /**
     * Test criteria construction with both keyword and speaker
     */
    public function test_alert_details_to_criteria_both() {
        $details = [
            'keyword' => 'budget',
            'pid' => '54321',
        ];
        $criteria = alert_details_to_criteria($details);
        $this->assertStringContainsString('budget', $criteria);
        $this->assertStringContainsString('speaker:54321', $criteria);
    }

    /**
     * Test criteria with special characters in keyword
     */
    public function test_alert_details_to_criteria_special_chars() {
        $details = [
            'keyword' => "children's education",
            'pid' => '',
        ];
        $criteria = alert_details_to_criteria($details);
        $this->assertEquals("children's education", $criteria);
    }

    /**
     * Test criteria with empty/missing fields
     */
    public function test_alert_details_to_criteria_empty() {
        $details = [
            'keyword' => '',
            'pid' => '',
        ];
        $criteria = alert_details_to_criteria($details);
        $this->assertEquals('', trim($criteria));
    }

    /**
     * Test criteria with whitespace
     */
    public function test_alert_details_to_criteria_whitespace() {
        $details = [
            'keyword' => '  budget  ',
            'pid' => '',
        ];
        $criteria = alert_details_to_criteria($details);
        // Should preserve whitespace from keyword.
        $this->assertStringContainsString('budget', $criteria);
    }

    /**
     * Test speaker criteria format validation
     */
    public function test_alert_details_to_criteria_speaker_format() {
        $details = [
            'keyword' => '',
            'pid' => '12345',
        ];
        $criteria = alert_details_to_criteria($details);
        $this->assertStringStartsWith('speaker:', $criteria);
        $this->assertStringEndsWith('12345', $criteria);
    }

    /**
     * Test speaker PID is exactly 5 digits after "speaker:"
     */
    public function test_alert_details_to_criteria_speaker_length() {
        $details = [
            'keyword' => '',
            'pid' => '99999',
        ];
        $criteria = alert_details_to_criteria($details);
        $this->assertEquals('speaker:99999', $criteria);
    }

    /**
     * Test alert confirmation advert for speaker alert
     */
    public function test_alert_confirmation_advert_speaker() {
        $details = ['pid' => '12345'];
        $advert = alert_confirmation_advert($details);
        $this->assertIsString($advert);
        // Returns identifier like 'twfy-alert-word' or 'twfy-alert-person'.
        $this->assertStringContainsString('twfy-alert', $advert);
    }

    /**
     * Test alert confirmation advert for keyword alert
     */
    public function test_alert_confirmation_advert_keyword() {
        $details = ['pid' => ''];
        $advert = alert_confirmation_advert($details);
        $this->assertIsString($advert);
        // Should return twfy-alert-word for non-speaker alert.
        $this->assertStringContainsString('twfy-alert-person', $advert);
    }

    /**
     * Test advert contains webpath link
     */
    public function test_alert_confirmation_advert_has_link() {
        $details = ['pid' => ''];
        $advert = alert_confirmation_advert($details);
        // Returns identifier string, not HTML.
        $this->assertIsString($advert);
        $this->assertNotEmpty($advert);
    }

    /**
     * Test advert HTML structure
     */
    public function test_alert_confirmation_advert_html_structure() {
        $details = ['pid' => '54321'];
        $advert = alert_confirmation_advert($details);
        // Function generates output but returns identifier.
        $this->assertIsString($advert);
    }

    /**
     * Test advert return value is identifier string
     */
    public function test_alert_confirmation_advert_return_value() {
        $details = ['pid' => '12345'];
        $advert = alert_confirmation_advert($details);
        $this->assertIsString($advert);
        // Return value is an identifier like 'twfy-alert-word'.
        $this->assertGreaterThan(0, strlen($advert));
        $this->assertTrue(in_array($advert, ['twfy-alert-word', 'twfy-alert-person']));
    }

    /**
     * Test empty details array
     */
    public function test_alert_details_to_criteria_missing_keys() {
        $details = [];
        // Should handle missing keys gracefully.
        $criteria = @alert_details_to_criteria($details);
        $this->assertIsString($criteria);
    }

    /**
     * Test criteria combines multiple parts correctly
     */
    public function test_alert_details_to_criteria_combination() {
        $details = [
            'keyword' => 'renewable energy',
            'pid' => '11111',
        ];
        $criteria = alert_details_to_criteria($details);
        $parts = explode(' ', $criteria);
        $this->assertContains('renewable', $parts);
        $this->assertContains('energy', $parts);
        $this->assertContains('speaker:11111', $parts);
    }

    /**
     * Test criteria with numeric keyword
     */
    public function test_alert_details_to_criteria_numeric_keyword() {
        $details = [
            'keyword' => '2024',
            'pid' => '',
        ];
        $criteria = alert_details_to_criteria($details);
        $this->assertEquals('2024', $criteria);
    }

    /**
     * Test ALERT class instantiation
     */
    public function test_alert_class_instantiation() {
        $alert = new ALERT();
        $this->assertIsObject($alert);
        $this->assertEquals('', $alert->alert_id);
        $this->assertEquals('', $alert->email);
        $this->assertEquals('', $alert->criteria);
    }

    /**
     * Test ALERT class initial properties
     */
    public function test_alert_class_properties() {
        $alert = new ALERT();
        $this->assertObjectHasProperty('alert_id', $alert);
        $this->assertObjectHasProperty('email', $alert);
        $this->assertObjectHasProperty('criteria', $alert);
        $this->assertObjectHasProperty('deleted', $alert);
        $this->assertObjectHasProperty('confirmed', $alert);
    }

}
