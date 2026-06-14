<?php

use PHPUnit\Framework\TestCase;

require_once INCLUDESPATH . 'utility.php';
require_once EASYPARLIAMENTPATH . 'alert.php';

class AlertTokenTest extends TestCase {

    // --- alert_details_to_criteria ---

    public function test_criteria_keyword_only(): void {
        $details = ['keyword' => 'climate', 'pid' => ''];
        $this->assertSame('climate', alert_details_to_criteria($details));
    }

    public function test_criteria_speaker_only(): void {
        $details = ['keyword' => '', 'pid' => '12345'];
        $this->assertSame('speaker:12345', alert_details_to_criteria($details));
    }

    public function test_criteria_both_keyword_and_speaker(): void {
        $details = ['keyword' => 'budget', 'pid' => '54321'];
        $this->assertSame('budget speaker:54321', alert_details_to_criteria($details));
    }

    public function test_criteria_empty_fields(): void {
        $details = ['keyword' => '', 'pid' => ''];
        $this->assertSame('', alert_details_to_criteria($details));
    }

    // --- ALERT::confirm token parsing ---

    public function test_confirm_rejects_empty_token(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->confirm(''));
    }

    public function test_confirm_rejects_token_without_separator(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->confirm('noseparator'));
    }

    public function test_confirm_rejects_non_numeric_id(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->confirm('abc-sometoken'));
    }

    public function test_confirm_rejects_empty_registration_token(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->confirm('123-'));
    }

    public function test_confirm_rejects_token_with_too_many_parts(): void {
        $alert = new ALERT();
        // Three parts is invalid (only 2 expected).
        $this->assertFalse($alert->confirm('1-2-3'));
    }

    // --- ALERT::delete token parsing ---

    public function test_delete_rejects_empty_token(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->delete(''));
    }

    public function test_delete_rejects_token_without_separator(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->delete('noseparator'));
    }

    public function test_delete_rejects_non_numeric_id(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->delete('abc-sometoken'));
    }

    public function test_delete_rejects_empty_registration_token(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->delete('123-'));
    }

    // --- ALERT::confirm supports :: separator (legacy) ---

    public function test_confirm_rejects_legacy_separator_non_numeric(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->confirm('abc::sometoken'));
    }

    public function test_delete_rejects_legacy_separator_non_numeric(): void {
        $alert = new ALERT();
        $this->assertFalse($alert->delete('abc::sometoken'));
    }

    // --- alert_confirmation_advert ---

    public function test_advert_returns_word_type_for_speaker(): void {
        ob_start();
        $result = alert_confirmation_advert(['pid' => '12345']);
        ob_end_clean();
        $this->assertSame('twfy-alert-word', $result);
    }

    public function test_advert_returns_person_type_for_keyword(): void {
        ob_start();
        $result = alert_confirmation_advert(['pid' => '']);
        ob_end_clean();
        $this->assertSame('twfy-alert-person', $result);
    }

    // --- ALERT accessor defaults ---

    public function test_new_alert_has_empty_defaults(): void {
        $alert = new ALERT();
        $this->assertSame('', $alert->alert_id());
        $this->assertSame('', $alert->email());
        $this->assertSame('', $alert->criteria());
        $this->assertSame('', $alert->deleted());
        $this->assertSame('', $alert->confirmed());
    }

}
