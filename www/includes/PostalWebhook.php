<?php

namespace OpenAustralia\TWFY;

/**
 * Handles one Postal webhook request, and returns the HTTP status to answer
 * with. Postal posts delivery events for mail we sent, signed with
 * X-Postal-Signature-256: a base64 RSA-SHA256 signature of the raw body.
 * See https://docs.postalserver.io/developer/webhooks
 */
class PostalWebhook {

    /**
     * The kinds of email send_email() tags its mail with. Mail without one of
     * these tags isn't ours to act on.
     */
    const OUR_TAGS = ['alert', 'confirmation', 'password', 'notice'];

    /**
     * The receiving server's reply starts with a 5.7.x enhanced status code
     * when it refused us for a policy or reputation reason, such as blocking
     * our sending address. That says nothing about whether the recipient's
     * address works.
     */
    const SENDER_SIDE_REFUSAL = '/\A\d{3}[ -]5\.7\.\d+/';

    /**
     * The events we act on, and the payload key that holds the message each
     * one is about. MessageBounced wraps the message that bounced in
     * original_message. MessageDeliveryFailed carries it as message.
     *
     * Delayed and held mail is still being retried, and Postal tells us how
     * it ended in a later event. Anything else is not ours.
     */
    const MESSAGE_KEY = [
        'MessageBounced' => 'original_message',
        'MessageDeliveryFailed' => 'message',
    ];

    /**
     * @var callable
     */
    private $keySource;

    /**
     * @var callable
     */
    private $logger;

    /**
     * @param callable $keySource
     *   Takes a bool, true to ask for a fresh copy, and returns the PEM public
     *   keys Postal currently publishes, or null if they can't be determined.
     * @param callable|null $logger
     *   Takes one line to log. Lines never contain an email address.
     */
    public function __construct(callable $keySource, ?callable $logger = null) {
        $this->keySource = $keySource;
        $this->logger = $logger ?? 'error_log';
    }

    /**
     * Handles one request.
     *
     * @param string $rawBody
     *   The request body exactly as received, which is what was signed.
     * @param string|null $signature
     *   The X-Postal-Signature-256 header, if any.
     *
     * @return int
     *   200 when handled or deliberately ignored, 403 when the request isn't
     *   signed by Postal, and 503 when the keys can't be fetched, so Postal
     *   retries instead of the event being lost.
     */
    public function handle(string $rawBody, ?string $signature): int {
        // Checked before the keys are fetched, so an unsigned request costs
        // us no outbound request.
        if ($signature === null || $signature === '') {
            return 403;
        }

        $pems = call_user_func($this->keySource, false);
        if ($pems === null) {
            $this->log('postal webhook: signing keys unavailable');
            return 503;
        }

        if (!$this->isSignedAfterRefresh($rawBody, $signature, $pems)) {
            $this->log('postal webhook: signature rejected');
            return 403;
        }

        $this->processEvent($rawBody);
        return 200;
    }

    /**
     * True if the signature verifies against the keys we have, or against a
     * fresh copy of them.
     *
     * Postal may have rotated its key since we cached the old one, so a
     * signature that fails gets one fresh copy before it is rejected. The key
     * source rate limits how often that really fetches.
     *
     * @param string $rawBody
     *   The request body exactly as received.
     * @param string $signature
     *   The base64 signature from the header.
     * @param string[] $pems
     *   The keys we have.
     *
     * @return bool
     *   Whether the signature is Postal's.
     */
    private function isSignedAfterRefresh(string $rawBody, string $signature, array $pems): bool {
        if ($this->isSigned($rawBody, $signature, $pems)) {
            return true;
        }
        $fresh = call_user_func($this->keySource, true);
        return $fresh !== null && $fresh !== $pems && $this->isSigned($rawBody, $signature, $fresh);
    }

    /**
     * Acts on one request that is known to come from Postal, suppressing the
     * recipient if it reports a failure for mail we sent.
     *
     * @param string $rawBody
     *   The request body exactly as received.
     */
    private function processEvent(string $rawBody): void {
        $body = json_decode($rawBody, true);
        $event = is_array($body) ? ($body['event'] ?? null) : null;
        $payload = is_array($body) ? ($body['payload'] ?? null) : null;
        if (!is_string($event) || !is_array($payload)) {
            $this->log('postal webhook: ignored, not an event');
            return;
        }

        if (!isset(self::MESSAGE_KEY[$event])) {
            $this->log("postal webhook: $event ignored");
            return;
        }

        $to = self::addressOfOurMail($payload[self::MESSAGE_KEY[$event]] ?? null);
        if ($to === null) {
            $this->log("postal webhook: $event ignored, not mail we tagged");
            return;
        }

        $output = is_string($payload['output'] ?? null) ? $payload['output'] : null;
        if ($event === 'MessageDeliveryFailed' && self::blamesOurServer($output)) {
            $this->log("postal webhook: $event ignored, refusal blames our server");
            return;
        }

        $created = EmailSuppressions::suppress($to, 'hard_bounce', $event, $output);
        $this->log("postal webhook: $event " . ($created ? 'suppressed' : 'already suppressed'));
    }

    /**
     * The recipient of a message, if it is mail we sent.
     *
     * @param mixed $message
     *   The message from the payload.
     *
     * @return string|null
     *   The address it was sent to, or null if it isn't ours to act on.
     */
    private static function addressOfOurMail($message): ?string {
        if (!is_array($message)) {
            return null;
        }
        $tag = $message['tag'] ?? null;
        $to = $message['to'] ?? null;
        return in_array($tag, self::OUR_TAGS, true) && is_string($to) ? $to : null;
    }

    /**
     * Whether the receiving server's reply says it refused us for a policy or
     * reputation reason (5.7.x), not because the address doesn't work. The
     * one-off import of past failures uses the same rule.
     *
     * @param string|null $reply
     *   The receiving server's reply, as Postal stored it.
     *
     * @return bool
     *   True for a 5.7.x refusal.
     */
    public static function blamesOurServer(?string $reply): bool {
        return $reply !== null && preg_match(self::SENDER_SIDE_REFUSAL, trim($reply)) === 1;
    }

    /**
     * True if the signature verifies against any of the published keys.
     *
     * @param string $rawBody
     *   The request body exactly as received.
     * @param string $signature
     *   The base64 signature from the header.
     * @param string[] $pems
     *   The published public keys.
     *
     * @return bool
     *   Whether any key verifies the signature.
     */
    private function isSigned(string $rawBody, string $signature, array $pems): bool {
        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }
        foreach ($pems as $pem) {
            if (openssl_verify($rawBody, $decoded, $pem, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     */
    private function log(string $line): void {
        call_user_func($this->logger, $line);
    }

}
