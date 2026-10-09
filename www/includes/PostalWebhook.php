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

        if (!$this->isSigned($rawBody, $signature, $pems)) {
            // Postal may have rotated its key since we cached the old one, so
            // ask for a fresh copy once before rejecting. The key source rate
            // limits how often that really fetches.
            $fresh = call_user_func($this->keySource, true);
            if ($fresh === null || $fresh === $pems || !$this->isSigned($rawBody, $signature, $fresh)) {
                $this->log('postal webhook: signature rejected');
                return 403;
            }
        }

        $body = json_decode($rawBody, true);
        $event = is_array($body) ? ($body['event'] ?? null) : null;
        $payload = is_array($body) ? ($body['payload'] ?? null) : null;
        if (!is_string($event) || !is_array($payload)) {
            $this->log('postal webhook: ignored, not an event');
            return 200;
        }

        // MessageBounced wraps the message that bounced in original_message.
        // Everything else we act on carries it as message.
        if ($event === 'MessageBounced') {
            $message = $payload['original_message'] ?? null;
        } elseif ($event === 'MessageDeliveryFailed') {
            $message = $payload['message'] ?? null;
        } else {
            // Delayed and held mail is still being retried, and Postal tells
            // us how it ended in a later event. Anything else is not ours.
            $this->log("postal webhook: $event ignored");
            return 200;
        }

        $tag = is_array($message) ? ($message['tag'] ?? null) : null;
        $to = is_array($message) ? ($message['to'] ?? null) : null;
        if (!in_array($tag, self::OUR_TAGS, true) || !is_string($to)) {
            $this->log("postal webhook: $event ignored, not mail we tagged");
            return 200;
        }

        $output = is_string($payload['output'] ?? null) ? $payload['output'] : null;
        if ($event === 'MessageDeliveryFailed' && $output !== null && preg_match(self::SENDER_SIDE_REFUSAL, $output)) {
            $this->log("postal webhook: $event ignored, refusal blames our server");
            return 200;
        }

        $created = EmailSuppressions::suppress($to, 'hard_bounce', $event, $output);
        $this->log("postal webhook: $event " . ($created ? 'suppressed' : 'already suppressed'));
        return 200;
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
