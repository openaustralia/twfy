<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use League\Plates\Engine;
use OpenAustralia\TWFY\AlertUnsubscribePage;
use OpenAustralia\TWFY\Models\Alert as AlertModel;

/**
 * The /alert/unsubscribe/ page's decisions and views, run in this process.
 * AlertUnsubscribePageIntegrationTest runs index.php itself, in another one.
 */
class AlertUnsubscribePageTest extends TransactionalTestCase {

    private Engine $engine;

    /**
     *
     */
    protected function setUp(): void {
        parent::setUp();
        foreach (['WEBPATH' => '/', 'CONTACTEMAIL' => 'contact@example.invalid'] as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
        $this->engine = new Engine(__DIR__ . '/../www/resources/views');
    }

    /**
     *
     */
    private function token(string $email = 'page.test@example.invalid'): string {
        $alert = AlertModel::create([
            'email' => $email,
            'criteria' => 'climate',
            'registrationtoken' => 'pagetesttoken',
            'confirmed' => true,
            'deleted' => false,
            'created' => date('Y-m-d H:i:s'),
        ]);
        return $alert->alert_id . '-pagetesttoken';
    }

    /**
     *
     */
    public function test_a_get_asks_first_and_removes_nothing() {
        $token = $this->token();

        $response = AlertUnsubscribePage::handle('GET', $token, new ALERT());

        $this->assertSame(200, $response['status']);
        $this->assertSame('alertunsubscribeconfirm', $response['page']);
        $this->assertSame('/alert/unsubscribe/?t=' . urlencode($token), $response['data']['actionUrl']);
        $this->assertSame(0, AlertModel::where('deleted', true)->where('email', 'page.test@example.invalid')->count());
    }

    /**
     *
     */
    public function test_a_post_removes_the_alerts() {
        $token = $this->token();

        $response = AlertUnsubscribePage::handle('POST', $token, new ALERT());

        $this->assertSame(200, $response['status']);
        $this->assertSame('alertunsubscribesucceeded', $response['page']);
        $this->assertSame(1, AlertModel::where('deleted', true)->where('email', 'page.test@example.invalid')->count());
    }

    /**
     *
     */
    public function test_a_bad_token_is_a_400_on_get_and_post() {
        foreach (['GET', 'POST'] as $method) {
            foreach (['1-nosuchtoken', ['bad']] as $token) {
                $response = AlertUnsubscribePage::handle($method, $token, new ALERT());

                $this->assertSame(400, $response['status']);
                $this->assertSame('alertunsubscribefailed', $response['page']);
            }
        }
    }

    /**
     *
     */
    public function test_the_confirm_view_posts_to_the_page_with_the_token() {
        $html = $this->engine->render('alert/unsubscribe-confirm', ['actionUrl' => '/alert/unsubscribe/?t=1-a%26b', 'signUpUrl' => '/alert/']);

        $this->assertStringContainsString('<form action="/alert/unsubscribe/?t=1-a%26b" method="post">', $html);
        $this->assertStringContainsString('Unsubscribe from all alerts', $html);
        $this->assertStringContainsString('<a href="/alert/">sign up for an alert again</a>', $html);
    }

    /**
     *
     */
    public function test_the_succeeded_view_links_back_to_sign_up() {
        $html = $this->engine->render('alert/unsubscribe-succeeded', ['signUpUrl' => '/alert/']);

        $this->assertStringContainsString('Your alerts have been unsubscribed', $html);
        $this->assertStringContainsString('<a href="/alert/">sign up for an alert again</a>', $html);
    }

    /**
     *
     */
    public function test_the_failed_view_offers_the_contact_address() {
        $html = $this->engine->render('alert/unsubscribe-failed', ['contactEmail' => 'contact@example.invalid']);

        $this->assertStringContainsString('appears to be incomplete', $html);
        $this->assertStringContainsString('mailto:contact@example.invalid', $html);
    }

}
