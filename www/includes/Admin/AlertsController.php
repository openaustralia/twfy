<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\AlertStats;
use OpenAustralia\TWFY\Models\Alert;

/**
 * The admin reports on email alerts.
 */
class AlertsController extends AdminController {

    /**
     * Every alert, grouped by whether it is active, deleted or unconfirmed.
     */
    public function index(): void {
        $this->page('admin_alerts', function () {
            $byCreated = ($_GET['o'] ?? '') === 'c';
            $order = $byCreated ? 'created' : 'email';
            $describe = fn (string $criteria): string => (new \SEARCHENGINE($criteria))->query_description_long();

            $lists = [];
            foreach (['Active alerts' => Alert::active(), 'Deleted alerts' => Alert::deletedAlerts(), 'Unconfirmed alerts' => Alert::unconfirmedAlerts()] as $title => $alerts) {
                $lists[$title] = AlertStats::listRows($alerts, $order, $describe, fn (int $id): string => $this->userUrl($id));
            }

            return $this->view('admin.alerts', ['totals' => AlertStats::totals(), 'lists' => $lists]);
        });
    }

    /**
     * Who signs up for alerts, and what for.
     */
    public function stats(): void {
        $this->page('alert_stats', function () {
            $name_for = function (int $person_id): ?string {
                $member = new \MEMBER(['person_id' => $person_id]);
                return $member->valid ? $member->full_name() : null;
            };

            return $this->view('admin.alert_stats', [
                'separate' => AlertStats::probablySeparateSignupRows(),
                'speakers' => AlertStats::speakerRows($name_for),
                'keywords' => AlertStats::keywordRows(),
            ]);
        });
    }

}
