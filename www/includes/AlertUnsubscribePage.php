<?php

namespace OpenAustralia\TWFY;

/**
 * What /alert/unsubscribe/ does with a request, and which view answers it.
 * A POST, which is what a mail app's Unsubscribe button sends, removes every
 * alert at the address. Anything else only asks first, because mail scanners
 * and link previews follow links and must not unsubscribe anyone.
 */
class AlertUnsubscribePage {

    /**
     * Decide the response to a request.
     *
     * @param string $method
     *   The HTTP request method.
     * @param mixed $token
     *   The t= value, which is an array for ?t[]=x.
     * @param \ALERT $alert
     *   Used to look up and remove the alerts.
     *
     * @return array
     *   Keys status (HTTP response code), page (the metadata.php page name),
     *   template (a Plates view) and data (what the view needs).
     */
    public static function handle(string $method, mixed $token, \ALERT $alert): array {
        if ($method === 'POST') {
            if ($alert->delete_all_at_address($token) === false) {
                return self::error();
            }
            return [
                'status' => 200,
                'page' => 'alertunsubscribesucceeded',
                'template' => 'alert/unsubscribe-succeeded',
                'data' => ['signUpUrl' => WEBPATH . 'alert/'],
            ];
        }

        if ($alert->email_for_token($token) === null) {
            return self::error();
        }
        return [
            'status' => 200,
            'page' => 'alertunsubscribeconfirm',
            'template' => 'alert/unsubscribe-confirm',
            'data' => [
                'actionUrl' => WEBPATH . 'alert/unsubscribe/?t=' . urlencode($token),
                'signUpUrl' => WEBPATH . 'alert/',
            ],
        ];
    }

    /**
     * The friendly error, for a token that doesn't match an alert.
     */
    private static function error(): array {
        return [
            'status' => 400,
            'page' => 'alertunsubscribefailed',
            'template' => 'alert/unsubscribe-failed',
            'data' => ['contactEmail' => CONTACTEMAIL],
        ];
    }

}
