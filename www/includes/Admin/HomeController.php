<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\Models\User;

/**
 * The admin home page: user figures and the newest users.
 */
class HomeController extends AdminController {

    /**
     * Shows the user figures and the 50 newest users.
     */
    public function index(): void {
        $this->page('admin_home', function () {
            $day = gmdate('Y-m-d H:i:s', time() - 86400);
            $week = gmdate('Y-m-d H:i:s', time() - 86400 * 7);

            $recent = User::orderByDesc('registrationtime')
              ->limit(50)
              ->get(['user_id', 'firstname', 'lastname', 'email', 'confirmed', 'registrationtime'])
              ->map(fn (User $user): array => [
                  'name' => $user->fullName(),
                  'url' => $user->confirmed ? $this->userUrl($user->user_id) : null,
                  'email' => $user->email,
                  'confirmed' => $user->confirmed ? 'Yes' : 'No',
                  'registered' => $user->registrationtime,
              ])
              ->all();

            return $this->view('admin.index', [
                'confirmed' => User::where('confirmed', 1)->count(),
                'unconfirmed' => User::where('confirmed', 0)->count(),
                'activeDay' => User::where('lastvisit', '>', $day)->count(),
                'activeWeek' => User::where('lastvisit', '>', $week)->count(),
                'recent' => $recent,
            ]);
        });
    }

}
