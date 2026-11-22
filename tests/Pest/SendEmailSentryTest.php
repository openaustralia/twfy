<?php

/**
 * @file
 */

use Sentry\Event;
use Sentry\EventType;
use Sentry\ResultStatus;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\Tracing\Span;
use Sentry\Tracing\SpanContext;
use Sentry\Transport\Result;
use Sentry\Transport\TransportInterface;

require_once __DIR__ . '/../../www/includes/utility.php';

beforeEach(function () {
    if (!defined('CONTACTEMAIL')) {
        define('CONTACTEMAIL', 'contact@example.invalid');
    }
    $this->events = new class() implements TransportInterface {

        /**
         * @var \Sentry\Event[]
         */
        public array $sent = [];

        public function send(Event $event): Result {
            $this->sent[] = $event;
            return new Result(ResultStatus::success(), $event);
        }

        public function close(?int $timeout = null): Result {
            return new Result(ResultStatus::success());
        }

    };
    // A zero rate shows send_email() samples its own transaction regardless.
    \Sentry\init([
        'dsn' => 'https://pubkey123@o99.ingest.sentry.io/1',
        'traces_sample_rate' => 0.0,
        'transport' => $this->events,
        // Without this, init() installs error handlers PHPUnit flags as risky.
        'default_integrations' => false,
    ]);
    $this->transaction = fn () => array_values(array_filter(
        $this->events->sent,
        fn (Event $e) => $e->getType() === EventType::transaction()
    ))[0] ?? null;
    $this->message = fn () => array_values(array_filter(
        $this->events->sent,
        fn (Event $e) => $e->getType() === EventType::event()
    ))[0] ?? null;
    set_mail_transport(fn () => true);
});

afterEach(function () {
    set_mail_transport(null);
    SentrySdk::setCurrentHub(new Hub());
});

describe('send_email()', function () {
    it('sends an email.send transaction', function () {
        send_email('alice@example.invalid', 'Hello', 'Body');

        expect(($this->transaction)()->getTransaction())->toBe('email.send');
    });

    it('is the current span while sending', function () {
        $op = null;
        set_mail_transport(function () use (&$op) {
            $op = SentrySdk::getCurrentHub()->getSpan()?->getOp();
            return true;
        });

        send_email('alice@example.invalid', 'Hello', 'Body');

        expect($op)->toBe('email.send');
    });

    it('restores the previous span', function () {
        $previous = new Span(new SpanContext());
        SentrySdk::getCurrentHub()->setSpan($previous);

        send_email('alice@example.invalid', 'Hello', 'Body');

        expect(SentrySdk::getCurrentHub()->getSpan())->toBe($previous);
    });

    it('tags whether the mail is bulk', function (bool $bulk, string $tag) {
        send_email('alice@example.invalid', 'Hello', 'Body', $bulk);

        expect(($this->transaction)()->getTags()['bulk'])->toBe($tag);
    })->with([
        'bulk' => [true, 'true'],
        'one-off' => [false, 'false'],
    ]);

    describe('when the mail is sent', function () {
        it('marks the transaction ok', function () {
            send_email('alice@example.invalid', 'Hello', 'Body');

            expect(($this->transaction)()->getContexts()['trace']['status'])->toBe('ok');
        });

        it('reports no failure', function () {
            send_email('alice@example.invalid', 'Hello', 'Body');

            expect(($this->message)())->toBeNull();
        });
    });

    describe('when the mail fails', function () {
        beforeEach(fn () => set_mail_transport(fn () => false));

        it('marks the transaction an error', function () {
            send_email('alice@example.invalid', 'Hello', 'Body');

            expect(($this->transaction)()->getContexts()['trace']['status'])->toBe('internal_error');
        });

        it('reports the failure', function () {
            send_email('alice@example.invalid', 'Hello', 'Body');

            expect(($this->message)()->getMessage())->toBe('mail() failed');
        });

        it('reports it as a warning', function () {
            send_email('alice@example.invalid', 'Hello', 'Body');

            expect(($this->message)()->getLevel())->toEqual(Severity::warning());
        });

        it('tags the failure bulk or not', function () {
            send_email('alice@example.invalid', 'Hello', 'Body', true);

            expect(($this->message)()->getTags()['bulk'])->toBe('true');
        });
    });
});
