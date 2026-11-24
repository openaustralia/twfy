<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\Models\Hansard;

/**
 * The admin page that counts what Hansard data the database holds.
 */
class StatisticsController extends AdminController {

    /**
     * The Hansard major number of House of Representatives debates.
     */
    private const DEBATES = 1;

    /**
     * The Hansard type of a speech.
     */
    private const SPEECH = 12;

    /**
     * Shows how much debate data there is.
     */
    public function show(): void {
        $this->page('admin_statistics', function () {
            $debates = Hansard::where('major', self::DEBATES);
            $sittingDays = Hansard::distinct()->count('hdate');

            $speeches = (clone $debates)->where('htype', self::SPEECH)->count();

            return $this->view('admin.statistics', [
                'from' => format_date((string) Hansard::min('hdate'), SHORTDATEFORMAT),
                'to' => format_date((string) Hansard::max('hdate'), SHORTDATEFORMAT),
                'sittingDays' => $sittingDays,
                'items' => $debates->count(),
                'speeches' => $speeches,
                'speechesPerDay' => $sittingDays > 0 ? round($speeches / $sittingDays) : 0,
            ]);
        });
    }

}
