<?php

namespace OpenAustralia\TWFY;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * The public keys Postal publishes for the key it signs webhooks with.
 *
 * Postal signs with one installation-wide key and publishes the matching
 * public key as a JWK Set at a well-known address, so we check signatures
 * against whatever it publishes now rather than holding our own copy. A key
 * rotation on the Postal server then needs no change here.
 * See https://docs.postalserver.io/developer/webhooks
 */
class PostalSigningKeys {

    /**
     * Postal gives up on a webhook after 5 seconds, and this runs inline in
     * that request, so a slower fetch than this is no use to us.
     */
    const HTTP_TIMEOUT = 2;

    /**
     * Long enough that a burst of webhooks doesn't mean a fetch each, short
     * enough that a rotation on the Postal server is picked up within the
     * retries Postal gives up after (about 36 minutes).
     */
    const CACHE_TTL = 900;

    /**
     * A failed fetch is cached too, briefly, so an outage can't turn into one
     * outbound request per inbound webhook on an endpoint anyone can post to.
     * Postal's first retry comes after this expires.
     */
    const FAILURE_TTL = 60;

    /**
     * The shortest time between refetches asked for after a signature failed
     * against the cached keys, so a flood of bad signatures can't become a
     * flood of fetches.
     */
    const REFRESH_INTERVAL = 60;

    private ClientInterface $http;

    private string $url;

    private ?string $cachePath;

    /**
     * @var callable
     */
    private $clock;

    /**
     * @param \GuzzleHttp\ClientInterface $http
     *   Used to fetch the key set.
     * @param string $url
     *   Where Postal publishes it.
     * @param string|null $cachePath
     *   A file only this process's user can write, or null to skip caching.
     * @param callable|null $clock
     *   Returns the current unix time. Injected so tests can move time.
     */
    public function __construct(ClientInterface $http, string $url, ?string $cachePath, ?callable $clock = null) {
        $this->http = $http;
        $this->url = $url;
        $this->cachePath = $cachePath;
        $this->clock = $clock ?? 'time';
    }

    /**
     * PEM public keys Postal currently publishes for signatures.
     *
     * @param bool $refresh
     *   True when a signature failed against the cached keys, which may mean
     *   Postal has rotated its key since. It refetches, but not more than once
     *   a minute.
     *
     * @return string[]|null
     *   The keys, or null if they can't be determined.
     */
    public function pems(bool $refresh = false): ?array {
        $answer = $this->cachedAnswer($refresh, (int) call_user_func($this->clock), $this->readCache());
        if ($answer !== null) {
            return $answer['pems'];
        }

        // Concurrent webhooks that all find the cache stale queue here, and
        // each looks again once it has the lock, so only the first fetches.
        $lock = $this->lock();
        try {
            $now = (int) call_user_func($this->clock);
            $cached = $this->readCache();
            $answer = $this->cachedAnswer($refresh, $now, $cached);
            if ($answer !== null) {
                return $answer['pems'];
            }

            $pems = $this->fetch();
            if ($pems === null && $refresh && $cached !== null && $cached['pems'] !== null) {
                // Keep the keys we have. The failure only has to hold off the
                // next refresh.
                $this->writeCache($cached['pems'], $cached['expires'], $now);
                return $cached['pems'];
            }

            $this->writeCache($pems, $now + ($pems === null ? self::FAILURE_TTL : self::CACHE_TTL), $now);
            return $pems;
        } finally {
            if ($lock !== null) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    /**
     * What the cache says to return without fetching, wrapped in an array
     * because a cached failure legitimately returns null keys. Null means the
     * cache has no answer and the keys have to be fetched.
     *
     * @return array|null
     *   Key pems, or null.
     */
    private function cachedAnswer(bool $refresh, int $now, ?array $cached): ?array {
        if ($cached === null) {
            return null;
        }
        if (!$refresh && $cached['expires'] > $now) {
            return ['pems' => $cached['pems']];
        }
        if ($refresh && $now - $cached['fetched'] < self::REFRESH_INTERVAL) {
            return ['pems' => $cached['pems']];
        }
        return null;
    }

    /**
     * An exclusive lock on a file beside the cache, or null when there is no
     * cache to protect or no lock file can be opened. Without a lock requests
     * only lose the once-a-minute limit during a burst, they still work.
     *
     * @return resource|null
     *   The open lock file, to unlock and close, or null.
     */
    private function lock() {
        if ($this->cachePath === null || !is_writable(dirname($this->cachePath))) {
            return null;
        }
        $handle = fopen($this->cachePath . '.lock', 'c');
        if ($handle === false) {
            return null;
        }
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return null;
        }
        return $handle;
    }

    /**
     * Fetches the key set and converts the signing keys to PEM.
     *
     * @return string[]|null
     *   The keys, or null if the fetch or the response was unusable.
     */
    private function fetch(): ?array {
        try {
            $response = $this->http->request('GET', $this->url, [
                'timeout' => self::HTTP_TIMEOUT,
                'connect_timeout' => self::HTTP_TIMEOUT,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            return null;
        }
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $parsed = json_decode((string) $response->getBody(), true);
        if (!is_array($parsed) || !isset($parsed['keys']) || !is_array($parsed['keys'])) {
            return null;
        }

        $pems = [];
        foreach ($parsed['keys'] as $jwk) {
            $pem = self::signingKeyPem($jwk);
            if ($pem !== null) {
                $pems[] = $pem;
            }
        }
        return $pems === [] ? null : $pems;
    }

    /**
     * Only RSA keys marked for signature use are taken. "use" is optional in
     * RFC 7517, so a key without one is accepted, while an encryption key
     * never is.
     */
    private static function signingKeyPem($jwk): ?string {
        if (!is_array($jwk) || ($jwk['kty'] ?? null) !== 'RSA') {
            return null;
        }
        if (isset($jwk['use']) && $jwk['use'] !== 'sig') {
            return null;
        }
        if (!isset($jwk['n'], $jwk['e']) || !is_string($jwk['n']) || !is_string($jwk['e'])) {
            return null;
        }

        $n = self::base64UrlDecode($jwk['n']);
        $e = self::base64UrlDecode($jwk['e']);
        if ($n === '' || $e === '') {
            return null;
        }

        $rsaPublicKey = self::derSequence(self::derInteger($n) . self::derInteger($e));
        // rsaEncryption, with NULL parameters.
        $algorithm = self::derSequence("\x06\x09\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01\x05\x00");
        $spki = self::derSequence($algorithm . self::derTlv("\x03", "\x00" . $rsaPublicKey));

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /**
     *
     */
    private static function base64UrlDecode(string $value): string {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? '' : $decoded;
    }

    /**
     * An unsigned big-endian integer, with a leading zero byte if the top bit
     * is set so it isn't read as negative.
     */
    private static function derInteger(string $bytes): string {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || (ord($bytes[0]) & 0x80)) {
            $bytes = "\x00" . $bytes;
        }
        return self::derTlv("\x02", $bytes);
    }

    /**
     *
     */
    private static function derSequence(string $contents): string {
        return self::derTlv("\x30", $contents);
    }

    /**
     *
     */
    private static function derTlv(string $tag, string $contents): string {
        $length = strlen($contents);
        if ($length < 0x80) {
            $encoded = chr($length);
        } else {
            $bytes = ltrim(pack('N', $length), "\x00");
            $encoded = chr(0x80 | strlen($bytes)) . $bytes;
        }
        return $tag . $encoded . $contents;
    }

    /**
     * What is cached, if anything usable is: when it expires, when it was
     * fetched, and the keys (null for a cached failure).
     *
     * @return array|null
     *   Keys expires, fetched and pems.
     */
    private function readCache(): ?array {
        if ($this->cachePath === null) {
            return null;
        }
        // Checked rather than silenced with @, because the site's error
        // handler reports even silenced warnings.
        if (!is_readable($this->cachePath)) {
            return null;
        }
        $json = file_get_contents($this->cachePath);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['expires'], $data['fetched']) || !array_key_exists('pems', $data)) {
            return null;
        }
        return $data;
    }

    /**
     * Written to a temporary file and renamed, so a concurrent request never
     * reads half a file. A cache that can't be written only costs a fetch.
     */
    private function writeCache(?array $pems, int $expires, int $fetched): void {
        if ($this->cachePath === null) {
            return;
        }
        $directory = dirname($this->cachePath);
        if (!is_dir($directory) || !is_writable($directory)) {
            return;
        }
        $temporary = tempnam($directory, 'twfy-postal-keys');
        if ($temporary === false) {
            return;
        }
        if (file_put_contents($temporary, json_encode(['expires' => $expires, 'fetched' => $fetched, 'pems' => $pems])) === false || !rename($temporary, $this->cachePath)) {
            unlink($temporary);
        }
    }

}
