<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\Models\Alert as AlertModel;

/**
 * ALERT::add(), ALERT::fetch() and suggest_alerts() against the alerts table.
 */
class AlertAddIntegrationTest extends TransactionalTestCase {

    /**
     *
     */
    private function makeAlert(string $email, string $criteria, bool $confirmed = true, bool $deleted = false): AlertModel {
        return AlertModel::create([
            'email' => $email,
            'criteria' => $criteria,
            'registrationtoken' => 'tokenexample',
            'confirmed' => $confirmed,
            'deleted' => $deleted,
            'created' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     *
     */
    public function test_adding_an_alert_confirms_it_straight_away_by_default() {
        $alert = new ALERT();

        $result = $alert->add(['email' => 'alice@example.invalid', 'pid' => '10001', 'recommended' => 0]);

        $this->assertSame(1, $result);
        $saved = AlertModel::findOrFail($alert->alert_id);
        $this->assertSame('speaker:10001', $saved->criteria);
        $this->assertTrue($saved->confirmed);
        $this->assertFalse($saved->deleted);
        $this->assertFalse($saved->recommended);
        $this->assertSame($alert->registrationtoken, $saved->registrationtoken);
    }

    /**
     *
     */
    public function test_adding_an_alert_without_instant_confirmation_leaves_it_unconfirmed() {
        $alert = new ALERT();

        $alert->add(['email' => 'alice@example.invalid', 'pid' => '10001', 'recommended' => 0], false, false);

        $this->assertFalse(AlertModel::findOrFail($alert->alert_id)->confirmed);
    }

    /**
     *
     */
    public function test_adding_a_recommended_alert_records_it() {
        $alert = new ALERT();

        $alert->add(['email' => 'alice@example.invalid', 'pid' => '10001', 'recommended' => 1]);

        $this->assertTrue(AlertModel::findOrFail($alert->alert_id)->recommended);
    }

    /**
     *
     */
    public function test_a_new_alert_is_stamped_with_the_database_clock() {
        $alert = new ALERT();
        $alert->add(['email' => 'alice@example.invalid', 'pid' => '10001', 'recommended' => 0]);

        $close = AlertModel::whereKey($alert->alert_id)
          ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, created, NOW())) < 2')
          ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, created_at, NOW())) < 2')
          ->exists();

        $this->assertTrue($close);
    }

    /**
     *
     */
    public function test_adding_an_alert_that_is_already_active_is_refused() {
        $this->makeAlert('alice@example.invalid', 'speaker:10001');

        $result = (new ALERT())->add(['email' => 'alice@example.invalid', 'pid' => '10001', 'recommended' => 0]);

        $this->assertSame(-2, $result);
        $this->assertSame(1, AlertModel::where('email', 'alice@example.invalid')->count());
    }

    /**
     *
     */
    public function test_adding_a_deleted_alert_brings_it_back() {
        $existing = $this->makeAlert('alice@example.invalid', 'speaker:10001', true, true);

        $result = (new ALERT())->add(['email' => 'alice@example.invalid', 'pid' => '10001', 'recommended' => 0]);

        $this->assertSame(1, $result);
        $this->assertFalse($existing->fresh()->deleted);
        $this->assertSame(1, AlertModel::where('email', 'alice@example.invalid')->count());
    }

    /**
     *
     */
    public function test_fetch_returns_only_the_alerts_asked_for() {
        $live = $this->makeAlert('live@example.invalid', 'speaker:10001');
        $this->makeAlert('gone@example.invalid', 'speaker:10001', true, true);
        $this->makeAlert('new@example.invalid', 'speaker:10001', false);

        $data = (new ALERT())->fetch(1, 0)['data'];

        $ids = array_column($data, 'alert_id');
        $this->assertContains($live->alert_id, $ids);
        $this->assertSame(['live@example.invalid'], array_values(array_filter(array_column($data, 'email'), fn ($email) => str_ends_with($email, '@example.invalid'))));
    }

    /**
     *
     */
    public function test_fetch_treats_its_arguments_as_values_not_sql() {
        $this->makeAlert('live@example.invalid', 'speaker:10001');

        $data = (new ALERT())->fetch('1 OR 1=1', 0)['data'];

        $this->assertNotEmpty($data);
        $this->assertGreaterThan(0, AlertModel::count());
    }

    /**
     *
     */
    public function test_suggest_alerts_offers_what_followers_also_follow() {
        $this->makeAlert('other@example.invalid', 'speaker:10001');
        $this->makeAlert('other@example.invalid', 'speaker:10002');

        ob_start();
        suggest_alerts('searcher@example.invalid', 'speaker:10001', 5);
        $output = ob_get_clean();

        $this->assertStringContainsString('You may also be interested', $output);
    }

    /**
     *
     */
    public function test_suggest_alerts_skips_what_the_searcher_already_follows() {
        $this->makeAlert('other@example.invalid', 'speaker:10001');
        $this->makeAlert('other@example.invalid', 'speaker:10002');
        $this->makeAlert('searcher@example.invalid', 'speaker:10001');
        $this->makeAlert('searcher@example.invalid', 'speaker:10002');

        ob_start();
        suggest_alerts('searcher@example.invalid', 'speaker:10001', 5);
        $output = ob_get_clean();

        $this->assertStringNotContainsString('You may also be interested', $output);
    }

}
