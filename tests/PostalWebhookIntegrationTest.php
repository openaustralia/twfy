<?php

/**
 * @file
 */

require_once __DIR__ . '/bootstrap.php';

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use OpenAustralia\TWFY\EmailSuppressions;
use OpenAustralia\TWFY\Models\EmailSuppression;
use OpenAustralia\TWFY\PostalSigningKeys;
use OpenAustralia\TWFY\PostalWebhook;

/**
 * The Postal webhook handler: raw body, signature header and a key source in,
 * HTTP status and suppression state out. Uses a generated key pair and never
 * contacts Postal.
 */
class PostalWebhookIntegrationTest extends TransactionalTestCase {

    /**
     * @var OpenSSLAsymmetricKey
     */
    private static $privateKey;

    /**
     * @var string
     */
    private static $publicPem;

    /**
     * @var array
     */
    private static $jwk;

    /**
     * @var int
     */
    private $keyFetches = 0;

    /**
     *
     */
    public static function setUpBeforeClass(): void {
        self::$privateKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $details = openssl_pkey_get_details(self::$privateKey);
        self::$publicPem = $details['key'];
        self::$jwk = [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => 'example',
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
        ];
    }

    /**
     *
     */
    private function sign(string $body, $key = null): string {
        openssl_sign($body, $signature, $key ?? self::$privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    /**
     * A handler whose key source counts fetches and quietly logs nothing.
     */
    private function handler(?array $pems = null, bool $unavailable = false): PostalWebhook {
        $pems ??= [self::$publicPem];
        return new PostalWebhook(function () use ($pems, $unavailable) {
            $this->keyFetches++;
            return $unavailable ? null : $pems;
        }, function () {
        });
    }

    /**
     *
     */
    private function failed(string $to = 'alice@example.invalid', string $output = '550 5.1.1 The email account does not exist', string $tag = 'alert'): string {
        return json_encode([
            'event' => 'MessageDeliveryFailed',
            'payload' => [
                'message' => ['id' => 1, 'to' => $to, 'tag' => $tag],
                'status' => 'HardFail',
                'details' => 'Message for ' . $to . ' was rejected',
                'output' => $output,
            ],
            'timestamp' => 1790000000,
            'uuid' => 'example-uuid',
        ]);
    }

    /**
     *
     */
    private function bounced(string $to = 'alice@example.invalid', string $tag = 'alert'): string {
        return json_encode([
            'event' => 'MessageBounced',
            'payload' => [
                'original_message' => ['id' => 1, 'to' => $to, 'tag' => $tag],
                'bounce' => ['id' => 2, 'subject' => 'Undelivered Mail Returned to Sender'],
            ],
            'timestamp' => 1790000000,
            'uuid' => 'example-uuid',
        ]);
    }

    /**
     *
     */
    private function post(string $body, ?PostalWebhook $handler = null): int {
        return ($handler ?? $this->handler())->handle($body, $this->sign($body));
    }

    /**
     *
     */
    public function test_a_signed_hard_fail_suppresses_the_recipient() {
        $this->assertSame(200, $this->post($this->failed()));

        $row = EmailSuppression::where('email', 'alice@example.invalid')->first();
        $this->assertNotNull($row);
        $this->assertSame('MessageDeliveryFailed', $row->postal_event);
        $this->assertSame('550 5.1.1 The email account does not exist', $row->reply_excerpt);
    }

    /**
     *
     */
    public function test_a_signed_bounce_report_suppresses_the_recipient_of_the_original_message() {
        $this->assertSame(200, $this->post($this->bounced()));

        $row = EmailSuppression::where('email', 'alice@example.invalid')->first();
        $this->assertSame('MessageBounced', $row->postal_event);
    }

    /**
     *
     */
    public function test_a_5_7_x_refusal_blames_our_server_and_suppresses_nothing() {
        foreach (['550 5.7.1 Service unavailable, sender blocked', '550-5.7.26 This mail is unauthenticated'] as $output) {
            $this->assertSame(200, $this->post($this->failed('alice@example.invalid', $output)));
        }

        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_a_reply_that_merely_mentions_5_7_still_suppresses() {
        $this->assertSame(200, $this->post($this->failed('alice@example.invalid', '550 5.1.1 user unknown (not 5.7.1)')));
        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_delayed_held_and_unknown_events_are_ignored_with_200() {
        foreach (['MessageDelayed', 'MessageHeld', 'MessageSent', 'DomainDNSError', 'SomethingNew'] as $event) {
            $body = json_encode(['event' => $event, 'payload' => ['message' => ['to' => 'alice@example.invalid', 'tag' => 'alert']]]);
            $this->assertSame(200, $this->post($body), $event);
        }

        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_an_unsigned_request_gets_403_before_any_key_is_fetched() {
        $handler = $this->handler();

        $this->assertSame(403, $handler->handle($this->failed(), null));
        $this->assertSame(403, $handler->handle($this->failed(), ''));

        $this->assertSame(0, $this->keyFetches);
        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_a_signature_from_another_key_gets_403() {
        $other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $body = $this->failed();

        $this->assertSame(403, $this->handler()->handle($body, $this->sign($body, $other)));
        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_a_body_changed_after_signing_gets_403() {
        $signature = $this->sign($this->failed('alice@example.invalid'));

        $this->assertSame(403, $this->handler()->handle($this->failed('bob@example.invalid'), $signature));
        $this->assertFalse(EmailSuppressions::isSuppressed('bob@example.invalid'));
    }

    /**
     *
     */
    public function test_a_signature_that_is_not_base64_gets_403() {
        $this->assertSame(403, $this->handler()->handle($this->failed(), '!!! not base64 !!!'));
    }

    /**
     *
     */
    public function test_a_signature_by_any_published_key_is_accepted() {
        $other = openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048]))['key'];

        $this->assertSame(200, $this->post($this->failed(), $this->handler([$other, self::$publicPem])));
        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     * Unavailable keys mean we can't tell whether the request is genuine, so
     * Postal should retry rather than the event being lost.
     */
    public function test_unavailable_keys_get_503_so_postal_retries() {
        $this->assertSame(503, $this->post($this->failed(), $this->handler(null, true)));
        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_mail_without_a_tag_we_set_is_ignored() {
        foreach (['', 'alert-123', 'something-else'] as $tag) {
            $this->assertSame(200, $this->post($this->failed('alice@example.invalid', '550 5.1.1 no such user', $tag)));
        }
        $noTag = json_encode(['event' => 'MessageDeliveryFailed', 'payload' => ['message' => ['to' => 'alice@example.invalid'], 'output' => '550 5.1.1 no']]);
        $this->assertSame(200, $this->post($noTag));

        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_every_kind_of_tag_we_set_is_accepted() {
        foreach (['alert', 'confirmation', 'password', 'notice'] as $i => $kind) {
            $this->post($this->failed("person$i@example.invalid", '550 5.1.1 no', $kind));
            $this->assertTrue(EmailSuppressions::isSuppressed("person$i@example.invalid"), $kind);
        }
    }

    /**
     *
     */
    public function test_a_repeated_bounce_changes_nothing() {
        $this->post($this->failed());
        $this->post($this->failed());
        $this->post($this->bounced());

        $this->assertSame(1, EmailSuppression::where('email', 'alice@example.invalid')->count());
    }

    /**
     *
     */
    public function test_a_bounce_after_a_lift_creates_a_fresh_suppression() {
        $this->post($this->failed());
        EmailSuppressions::lift('alice@example.invalid');

        $this->post($this->failed());

        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
        $this->assertSame(2, EmailSuppression::where('email', 'alice@example.invalid')->count());
    }

    /**
     *
     */
    public function test_case_and_whitespace_in_the_recipient_are_normalised() {
        $this->post($this->failed('  Alice@Example.INVALID '));

        $this->assertSame(1, EmailSuppression::where('email', 'alice@example.invalid')->count());
    }

    /**
     *
     */
    public function test_a_signed_body_that_is_not_json_is_accepted_and_ignored() {
        $this->assertSame(200, $this->post('not json at all'));
    }

    /**
     *
     */
    public function test_the_log_has_the_event_and_outcome_but_no_address() {
        $lines = [];
        $handler = new PostalWebhook(fn() => [self::$publicPem], function ($line) use (&$lines) {
            $lines[] = $line;
        });

        $this->post($this->failed('alice@example.invalid'), $handler);

        $this->assertNotEmpty($lines);
        foreach ($lines as $line) {
            $this->assertStringNotContainsString('alice', $line);
            $this->assertStringNotContainsString('example.invalid', $line);
        }
        $this->assertStringContainsString('MessageDeliveryFailed', implode(' ', $lines));
    }

    /**
     * A signature from a key Postal rotated in since we cached the old one is
     * accepted after one rate-limited refetch, so a rotation needs no change
     * here and no events are lost to a stale cache.
     */
    public function test_a_rotated_key_is_picked_up_by_refreshing_once() {
        $other = openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048]))['key'];
        $refreshes = 0;
        $handler = new PostalWebhook(function ($refresh = false) use ($other, &$refreshes) {
            if ($refresh) {
                $refreshes++;
                return [self::$publicPem];
            }
            return [$other];
        }, function () {
        });

        $this->assertSame(200, $this->post($this->failed(), $handler));
        $this->assertSame(1, $refreshes);
        $this->assertTrue(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     *
     */
    public function test_a_wrong_signature_still_gets_403_after_the_refresh() {
        $other = openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048]))['key'];
        $handler = new PostalWebhook(fn() => [$other], function () {
        });

        $this->assertSame(403, $this->post($this->failed(), $handler));
        $this->assertFalse(EmailSuppressions::isSuppressed('alice@example.invalid'));
    }

    /**
     * A refresh that fails is not a reason to reject a request outright, but
     * it also can't make it genuine.
     */
    public function test_a_failed_refresh_gets_403_not_503() {
        $other = openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048]))['key'];
        $handler = new PostalWebhook(fn($refresh = false) => $refresh ? null : [$other], function () {
        });

        $this->assertSame(403, $this->post($this->failed(), $handler));
    }

    // Key fetching.

    /**
     * @param \GuzzleHttp\Psr7\Response[] $responses
     */
    private function keys(array $responses, ?string $cachePath = null, ?int &$now = null, ?array &$history = null): PostalSigningKeys {
        $history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        $cachePath ??= tempnam(sys_get_temp_dir(), 'twfy-keys-test');
        unlink($cachePath);
        $this->cleanup[] = $cachePath;
        $now ??= 1790000000;
        return new PostalSigningKeys(
            new Client(['handler' => $stack]),
            'https://postal.example.invalid/.well-known/jwks.json',
            $cachePath,
            function () use (&$now) {
                return $now;
            }
        );
    }

    /**
     * @var string[]
     */
    private $cleanup = [];

    /**
     *
     */
    protected function tearDown(): void {
        foreach ($this->cleanup as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    /**
     *
     */
    private function jwks(array $keys): Response {
        return new Response(200, [], json_encode(['keys' => $keys]));
    }

    /**
     *
     */
    public function test_a_published_jwk_becomes_a_key_that_verifies_signatures() {
        $keys = $this->keys([$this->jwks([self::$jwk])])->pems();

        $this->assertCount(1, $keys);
        $body = 'hello';
        openssl_sign($body, $signature, self::$privateKey, OPENSSL_ALGO_SHA256);
        $this->assertSame(1, openssl_verify($body, $signature, $keys[0], OPENSSL_ALGO_SHA256));
    }

    /**
     *
     */
    public function test_only_rsa_keys_for_signatures_are_used() {
        $keys = $this->keys([$this->jwks([
            array_merge(self::$jwk, ['use' => 'enc']),
            array_merge(self::$jwk, ['kty' => 'oct']),
            array_merge(self::$jwk, ['n' => 12345]),
            'not a key',
            array_diff_key(self::$jwk, ['use' => 1]),
        ])
])->pems();

        $this->assertCount(1, $keys);
    }

    /**
     *
     */
    public function test_a_response_that_cannot_be_used_means_no_keys() {
        foreach ([new Response(500, [], 'oops'), new Response(200, [], 'not json'), new Response(200, [], '{"keys":[]}'), new Response(200, [], '{"nokeys":1}')] as $response) {
            $this->assertNull($this->keys([$response])->pems());
        }
    }

    /**
     *
     */
    public function test_a_network_failure_means_no_keys() {
        $keys = $this->keys([new ConnectException('timed out', new Request('GET', 'https://postal.example.invalid/'))]);

        $this->assertNull($keys->pems());
    }

    /**
     *
     */
    public function test_keys_are_cached_so_a_burst_of_webhooks_fetches_once() {
        $now = 1790000000;
        $keys = $this->keys([$this->jwks([self::$jwk]), $this->jwks([self::$jwk])], null, $now, $history);

        $keys->pems();
        $keys->pems();
        $keys->pems();

        $this->assertCount(1, $history);
    }

    /**
     * Caching a failure is what stops an outage turning into one outbound
     * request per inbound webhook.
     */
    public function test_a_failed_fetch_is_cached_too_for_a_short_while() {
        $now = 1790000000;
        $keys = $this->keys([new Response(500), $this->jwks([self::$jwk])], null, $now, $history);

        $this->assertNull($keys->pems());
        $this->assertNull($keys->pems());
        $this->assertCount(1, $history);

        // After the failure's short life, Postal's retry fetches again and heals.
        $now += 120;
        $this->assertCount(1, $keys->pems() ?? []);
    }

    /**
     *
     */
    public function test_without_a_cache_path_every_call_fetches() {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([$this->jwks([self::$jwk]), $this->jwks([self::$jwk])]));
        $stack->push(Middleware::history($history));
        $keys = new PostalSigningKeys(new Client(['handler' => $stack]), 'https://postal.example.invalid/', null);

        $keys->pems();
        $keys->pems();

        $this->assertCount(2, $history);
    }

    /**
     * Asked to refresh, it refetches, but not more than once a minute, so a
     * flood of bad signatures can't become a flood of fetches.
     */
    public function test_a_refresh_refetches_but_only_once_a_minute() {
        $now = 1790000000;
        $keys = $this->keys([$this->jwks([self::$jwk]), $this->jwks([self::$jwk]), $this->jwks([self::$jwk])], null, $now, $history);

        $keys->pems();
        $this->assertCount(1, $history);

        $now += 10;
        $keys->pems(true);
        $this->assertCount(1, $history);

        $now += 61;
        $keys->pems(true);
        $this->assertCount(2, $history);

        $keys->pems(true);
        $this->assertCount(2, $history);
    }

    /**
     * A failed refresh keeps the keys we already have rather than replacing
     * them with a failure.
     */
    public function test_a_failed_refresh_keeps_the_cached_keys() {
        $now = 1790000000;
        $keys = $this->keys([$this->jwks([self::$jwk]), new Response(500)], null, $now, $history);

        $keys->pems();
        $now += 120;

        $this->assertCount(1, $keys->pems(true));
        $this->assertCount(1, $keys->pems());
    }

    /**
     *
     */
    public function test_cached_keys_expire_so_a_rotation_is_picked_up() {
        $now = 1790000000;
        $keys = $this->keys([$this->jwks([self::$jwk]), $this->jwks([self::$jwk])], null, $now, $history);

        $keys->pems();
        $now += 16 * 60;
        $keys->pems();

        $this->assertCount(2, $history);
    }

}
