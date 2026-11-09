<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;
use Sentry\Breadcrumb;
use Sentry\Event;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/SentryScrubber.php';

/**
 * sentry_scrub_event() removes email-address-shaped strings from breadcrumbs
 * and request data before an event leaves the server.
 */
class SentryScrubberTest extends TestCase {

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
            'cookies' => ['remember' => 'jane.citizen@example.org.au'],
            'headers' => ['Referer' => ['https://example.org/?e=a.person@example.com']],
            'data' => ['email' => 'jane.citizen@example.org.au'],
        ]);

        $request = sentry_scrub_event($event)->getRequest();

        $this->assertSame('email=[FILTERED]&q=housing', $request['query_string']);
        $this->assertSame(['remember' => '[FILTERED]'], $request['cookies']);
        $this->assertSame(['Referer' => ['https://example.org/?e=[FILTERED]']], $request['headers']);
        $this->assertSame(['email' => '[FILTERED]'], $request['data']);
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
        $event->setRequest(['query_string' => 'q=housing', 'cookies' => ['theme' => 'dark']]);

        $result = sentry_scrub_event($event);

        $this->assertSame('Viewed the debates page', $result->getBreadcrumbs()[0]->getMessage());
        $this->assertSame(['page' => 'debates'], $result->getBreadcrumbs()[0]->getMetadata());
        $this->assertSame('q=housing', $result->getRequest()['query_string']);
        $this->assertSame(['theme' => 'dark'], $result->getRequest()['cookies']);
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
