<?php

/**
 * @file
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use OpenAustralia\TWFY\PostalSigningKeys;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

/**
 * Where PostalSigningKeys caches keys, and what it does with keys and caches
 * that are no use. Needs no database. The fetching and verifying is covered
 * by PostalWebhookIntegrationTest.
 */
class PostalSigningKeysTest extends TestCase {

    private string $tempDir;

    /**
     *
     */
    protected function setUp(): void {
        $this->tempDir = sys_get_temp_dir() . '/twfy-keys-test-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0700);
    }

    /**
     *
     */
    protected function tearDown(): void {
        exec('rm -rf ' . escapeshellarg($this->tempDir));
    }

    /**
     *
     */
    private function keys(array $responses, ?string $cachePath): PostalSigningKeys {
        return new PostalSigningKeys(
            new Client(['handler' => HandlerStack::create(new MockHandler($responses))]),
            'https://postal.example.invalid/.well-known/jwks.json',
            $cachePath,
            fn() => 1790000000
        );
    }

    /**
     *
     */
    private function jwks(array $keys): Response {
        return new Response(200, [], json_encode(['keys' => $keys]));
    }

    /**
     * Any RSA key will do for these tests, which only check that one is made.
     */
    private function rsaKey(string $n = 'sQ3xvHk0aQ', string $e = 'AQAB'): array {
        return ['kty' => 'RSA', 'use' => 'sig', 'n' => $n, 'e' => $e];
    }

    /**
     *
     */
    public function test_it_makes_a_private_directory_and_caches_in_it() {
        $uid = posix_geteuid();

        $path = PostalSigningKeys::cachePath($this->tempDir, $uid);

        $this->assertSame($this->tempDir . '/twfy-postal-' . $uid . '/signing-keys.json', $path);
        $this->assertSame(0700, fileperms(dirname($path)) & 0777);
    }

    /**
     *
     */
    public function test_it_will_not_cache_in_a_directory_others_can_write() {
        $uid = posix_geteuid();
        mkdir($this->tempDir . '/twfy-postal-' . $uid, 0777);
        chmod($this->tempDir . '/twfy-postal-' . $uid, 0777);

        $this->assertNull(PostalSigningKeys::cachePath($this->tempDir, $uid));
    }

    /**
     *
     */
    public function test_it_will_not_cache_in_a_directory_another_user_owns() {
        $other = posix_geteuid() + 1;

        $this->assertNull(PostalSigningKeys::cachePath($this->tempDir, $other));
    }

    /**
     *
     */
    public function test_it_will_not_cache_through_a_symlink() {
        $uid = posix_geteuid();
        mkdir($this->tempDir . '/elsewhere', 0700);
        symlink($this->tempDir . '/elsewhere', $this->tempDir . '/twfy-postal-' . $uid);

        $this->assertNull(PostalSigningKeys::cachePath($this->tempDir, $uid));
    }

    /**
     *
     */
    public function test_a_key_with_no_modulus_is_no_key() {
        $keys = $this->keys([$this->jwks([$this->rsaKey('')])], null);

        $this->assertNull($keys->pems());
    }

    /**
     *
     */
    public function test_a_cache_file_that_is_not_json_is_ignored_and_replaced() {
        $path = $this->tempDir . '/signing-keys.json';
        file_put_contents($path, 'not json');

        $pems = $this->keys([$this->jwks([$this->rsaKey()])], $path)->pems();

        $this->assertCount(1, $pems);
        $this->assertSame($pems, json_decode(file_get_contents($path), true)['pems']);
    }

    /**
     *
     */
    public function test_it_still_answers_when_the_cache_directory_does_not_exist() {
        $path = $this->tempDir . '/missing/signing-keys.json';

        $pems = $this->keys([$this->jwks([$this->rsaKey()])], $path)->pems();

        $this->assertCount(1, $pems);
        $this->assertFileDoesNotExist($path);
    }

}
