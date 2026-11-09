<?php

/**
 * @file
 */

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\Integration\RequestFetcherInterface;
use Sentry\Integration\RequestIntegration;
use Sentry\ResultStatus;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\Transport\Result;
use Sentry\Transport\TransportInterface;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/SentryScrubber.php';

/**
 * sentry_scrub_event() removes login credentials and email-address-shaped
 * strings from breadcrumbs and request data before an event leaves the server.
 */
class SentryScrubberTest extends TestCase {

    /**
     * A fictional epuser_id value, in the real <user_id>.<md5 hex> form.
     */
    private const LOGIN_TOKEN = '123.0123456789abcdef0123456789abcdef';

    protected function setUp(): void {
        parent::setUp();
        SentrySdk::setCurrentHub(new Hub());
    }

    protected function tearDown(): void {
        parent::tearDown();
        SentrySdk::setCurrentHub(new Hub());
    }

    /**
     * An email address in a breadcrumb message is replaced.
     */
    public function test_scrubs_email_from_breadcrumb_message() {
        $event = Event::createEvent();
        $event->setBreadcrumb([
            new Breadcrumb(Breadcrumb::LEVEL_INFO, Breadcrumb::TYPE_DEFAULT, 'auth', 'Login for jane.citizen@example.org.au failed'),
        ]);

        $crumbs = sentry_scrub_event($event)->getBreadcrumbs();

        $this->assertSame('Login for [FILTERED] failed', $crumbs[0]->getMessage());
    }

    /**
     * Email addresses in breadcrumb metadata are replaced, including inside
     * nested arrays, and non-string values are left alone.
     */
    public function test_scrubs_email_from_breadcrumb_metadata() {
        $event = Event::createEvent();
        $event->setBreadcrumb([
            new Breadcrumb(Breadcrumb::LEVEL_INFO, Breadcrumb::TYPE_DEFAULT, 'mail', 'Sent', [
                'to' => 'jane.citizen@example.org.au',
                'cc' => ['a.person@example.com', 'no address here'],
                'count' => 3,
            ]),
        ]);

        $metadata = sentry_scrub_event($event)->getBreadcrumbs()[0]->getMetadata();

        $this->assertSame('[FILTERED]', $metadata['to']);
        $this->assertSame(['[FILTERED]', 'no address here'], $metadata['cc']);
        $this->assertSame(3, $metadata['count']);
    }

    /**
     * Each scrubbed request field has its email addresses replaced.
     */
    public function test_scrubs_email_from_request_fields() {
        $event = Event::createEvent();
        $event->setRequest([
            'url' => 'https://example.org/alert',
            'query_string' => 'email=jane.citizen@example.org.au&q=housing',
            'headers' => ['Referer' => ['https://example.org/?e=a.person@example.com']],
            'data' => ['email' => 'jane.citizen@example.org.au'],
        ]);

        $request = sentry_scrub_event($event)->getRequest();

        $this->assertSame('email=[FILTERED]&q=housing', $request['query_string']);
        $this->assertSame(['Referer' => ['https://example.org/?e=[FILTERED]']], $request['headers']);
        $this->assertSame(['email' => '[FILTERED]'], $request['data']);
    }

    /**
     * Request cookies are dropped, so the epuser_id login token never leaves
     * the server.
     */
    public function test_drops_request_cookies() {
        $event = Event::createEvent();
        $event->setRequest([
            'url' => 'https://example.org/',
            'cookies' => ['epuser_id' => self::LOGIN_TOKEN, 'theme' => 'dark'],
        ]);

        $request = sentry_scrub_event($event)->getRequest();

        $this->assertArrayNotHasKey('cookies', $request);
        $this->assertStringNotContainsString(self::LOGIN_TOKEN, json_encode($request));
    }

    /**
     * Credential headers are dropped whatever their case, and other headers stay.
     */
    public function test_drops_credential_headers_but_keeps_others() {
        $event = Event::createEvent();
        $event->setRequest([
            'headers' => [
                'Cookie' => ['epuser_id=' . self::LOGIN_TOKEN . '; theme=dark'],
                'set-cookie' => ['epuser_id=' . self::LOGIN_TOKEN],
                'AUTHORIZATION' => ['Bearer not-a-real-token'],
                'User-Agent' => ['Mozilla/5.0'],
            ],
        ]);

        $request = sentry_scrub_event($event)->getRequest();

        $this->assertSame(['User-Agent' => ['Mozilla/5.0']], $request['headers']);
        $this->assertStringNotContainsString(self::LOGIN_TOKEN, json_encode($request));
    }

    /**
     * Through the real SDK, with send_default_pii on as in init.php, the
     * epuser_id cookie is not in the event handed to the transport. Runs in
     * its own process because the SDK registers RequestIntegration once per
     * process, so an earlier test's \Sentry\init() would keep its fetcher.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_login_cookie_is_not_sent_through_the_sdk() {
        // The SDK's own request fetcher returns nothing under the CLI, so
        // hand its RequestIntegration a request carrying the login cookie.
        $request = (new ServerRequest('GET', 'https://example.org/', ['Cookie' => 'epuser_id=' . self::LOGIN_TOKEN . '; theme=dark'], null, '1.1', ['REMOTE_ADDR' => '203.0.113.7']))
          ->withCookieParams(['epuser_id' => self::LOGIN_TOKEN, 'theme' => 'dark']);
        $fetcher = new class($request) implements RequestFetcherInterface {

            public function __construct(private ServerRequestInterface $request) {
            }

            public function fetchRequest(): ?ServerRequestInterface {
                return $this->request;
            }

        };

        $transport = new class() implements TransportInterface {

            public ?Event $sent = null;

            public function send(Event $event): Result {
                $this->sent = $event;
                return new Result(ResultStatus::success(), $event);
            }

            public function close(?int $timeout = null): Result {
                return new Result(ResultStatus::success());
            }

        };
        \Sentry\init([
            'dsn' => 'https://pubkey123@o99.ingest.sentry.io/1',
            'send_default_pii' => true,
            'before_send' => 'sentry_scrub_event',
            'transport' => $transport,
            'integrations' => [new RequestIntegration($fetcher)],
        ]);

        \Sentry\captureMessage('Something went wrong');

        $this->assertNotNull($transport->sent, 'The event should reach the transport.');
        $this->assertSame('https://example.org/', $transport->sent->getRequest()['url'], 'The SDK should have attached the request.');
        $this->assertStringNotContainsString(self::LOGIN_TOKEN, json_encode($transport->sent->getRequest()));
    }

    /**
     * Request fields outside the scrubbed list, such as the URL, are not changed.
     */
    public function test_leaves_other_request_fields_alone() {
        $event = Event::createEvent();
        $event->setRequest(['url' => 'https://example.org/mp/jane@example.org.au']);

        $request = sentry_scrub_event($event)->getRequest();

        $this->assertSame('https://example.org/mp/jane@example.org.au', $request['url']);
    }

    /**
     * An event with no email addresses comes back unchanged.
     */
    public function test_leaves_event_without_email_unchanged() {
        $event = Event::createEvent();
        $event->setBreadcrumb([
            new Breadcrumb(Breadcrumb::LEVEL_INFO, Breadcrumb::TYPE_DEFAULT, 'nav', 'Viewed the debates page', ['page' => 'debates']),
        ]);
        $event->setRequest(['query_string' => 'q=housing', 'headers' => ['User-Agent' => ['Mozilla/5.0']]]);

        $result = sentry_scrub_event($event);

        $this->assertSame('Viewed the debates page', $result->getBreadcrumbs()[0]->getMessage());
        $this->assertSame(['page' => 'debates'], $result->getBreadcrumbs()[0]->getMetadata());
        $this->assertSame('q=housing', $result->getRequest()['query_string']);
        $this->assertSame(['User-Agent' => ['Mozilla/5.0']], $result->getRequest()['headers']);
    }

    /**
     * An event with no breadcrumbs and no request still passes through, and
     * the scrubber adds no request fields.
     */
    public function test_handles_event_with_no_breadcrumbs_or_request() {
        $event = Event::createEvent();

        $result = sentry_scrub_event($event);

        $this->assertSame($event, $result);
        $this->assertSame([], $result->getBreadcrumbs());
        $this->assertSame([], $result->getRequest());
    }

    /**
     * The scrubber also accepts transaction events, which Sentry sends to
     * before_send_transaction.
     */
    public function test_scrubs_transaction_events() {
        $event = Event::createTransaction();
        $event->setRequest(['query_string' => 'email=jane.citizen@example.org.au']);

        $result = sentry_scrub_event($event);

        $this->assertSame('email=[FILTERED]', $result->getRequest()['query_string']);
    }

}
