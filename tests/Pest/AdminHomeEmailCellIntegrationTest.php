<?php

/**
 * @file
 * The recently registered users table on the admin home page. Uses fictional
 * people.
 *
 * The page runs in its own PHP process against the committed database, so the
 * rows here are committed and removed again afterwards instead of rolled back.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\User;

const ADMIN_HOME_SPEC_SURNAME = 'Adminhomespec';

/**
 * Renders www/docs/admin/index.php as the user with this cookie value.
 */
function admin_home_spec_render(string $cookie): string {
    $config = getTestDbConfig();
    $script = realpath(__DIR__ . '/../../www/docs/admin/index.php');
    $constants = [
        'DB_HOST' => $config['host'],
        'DB_USER' => $config['user'],
        'DB_PASSWORD' => $config['pass'],
        'DB_NAME' => $config['name'],
        'DOMAIN' => 'localhost',
        'COOKIEDOMAIN' => 'localhost',
        'CONTACTEMAIL' => 'test@example.com',
        'BASEDIR' => realpath(__DIR__ . '/../../www/docs') . '/',
        'WEBPATH' => '/',
        'DEVSITE' => true,
        'DEBUGTAG' => 'debug',
        'TIMEZONE' => 'Australia/Sydney',
        'RAWDATA' => '/tmp/pwdata/',
        'PWMEMBERS' => '/tmp/pwdata/members/',
        'DBBACKUP' => '/tmp/backup/',
        'INCLUDESPATH' => realpath(__DIR__ . '/../../www/includes') . '/',
        'IMAGEPATH' => '/images/',
        'FILEIMAGEPATH' => realpath(__DIR__ . '/../../www/docs/images') . '/',
        'REGMEMPDFPATH' => 'regmem/scan/',
        'METADATAPATH' => realpath(__DIR__ . '/../../www/includes/easyparliament/metadata.php'),
        'XAPIANDB' => '/tmp/searchdb',
        'RECESSFILE' => '/dev/null',
    ];

    $code = '';
    foreach ($constants as $name => $value) {
        $code .= 'define(' . var_export($name, true) . ', ' . var_export($value, true) . ');';
    }
    $code .= '$_SERVER["DEVICE_TYPE"] = "desktop";'
        . '$_SERVER["REQUEST_METHOD"] = "GET";'
        . '$_SERVER["REQUEST_URI"] = "/admin/";'
        . '$_SERVER["HTTP_HOST"] = "localhost";'
        . '$_GET = []; $_POST = [];'
        . '$_COOKIE = ["epuser_id" => ' . var_export($cookie, true) . '];'
        . 'chdir(dirname(' . var_export($script, true) . '));'
        . 'include ' . var_export($script, true) . ';';

    $process = proc_open(
        ['php', '-d', 'display_errors=stderr', '-r', $code],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname($script),
        getenv()
    );
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return $output;
}

beforeEach(function () {
    if (getSharedTestConnection() === null) {
        $this->markTestSkipped('Database connection not available');
    }
    if (!str_contains((string) getenv('DB_NAME'), 'test')) {
        $this->markTestSkipped('This spec only runs against a database whose name contains "test"');
    }

    $this->clean = fn () => User::where('lastname', ADMIN_HOME_SPEC_SURNAME)->delete();
    ($this->clean)();

    $boss = User::create([
        'firstname' => 'Bea',
        'lastname' => ADMIN_HOME_SPEC_SURNAME,
        'email' => 'bea.adminhomespec@example.test',
        'status' => 'Superuser',
        'password' => 'stored-hash',
        'registrationtime' => '2099-01-02 00:00:00',
    ]);
    $this->cookie = $boss->user_id . '.' . md5('stored-hash');
});

afterEach(function () {
    if (isset($this->clean)) {
        ($this->clean)();
    }
});

/**
 * Registers a fictional person and renders the page for the Superuser.
 */
function admin_home_spec_page_with(string $email, string $cookie): string {
    User::create([
        'firstname' => 'Ada',
        'lastname' => ADMIN_HOME_SPEC_SURNAME,
        'email' => $email,
        'status' => 'User',
        'password' => 'another-hash',
        'registrationtime' => '2099-01-03 00:00:00',
    ]);

    return admin_home_spec_render($cookie);
}

describe('the admin home page', function () {
    describe('with an ordinary address', function () {
        it('renders the link as before', function () {
            $html = admin_home_spec_page_with('ada@example.test', $this->cookie);

            expect($html)->toContain('<a href="mailto:ada@example.test">ada@example.test</a>');
        });
    });

    describe('with an apostrophe and an ampersand', function () {
        beforeEach(function () {
            $this->html = admin_home_spec_page_with("o'neil&sons@example.test", $this->cookie);
        });

        it('encodes the link target', function () {
            expect($this->html)->toContain('href="mailto:o&#039;neil&amp;sons@example.test"');
        });

        it('encodes the link text', function () {
            expect($this->html)->toContain('>o&#039;neil&amp;sons@example.test</a>');
        });

        it('does not print the raw address', function () {
            expect($this->html)->not->toContain("o'neil&sons@example.test");
        });
    });

    describe('with angle brackets and a double quote', function () {
        it('encodes them in the link', function () {
            $html = admin_home_spec_page_with('"ada"<team>@example.test', $this->cookie);

            expect($html)->toContain('<a href="mailto:&quot;ada&quot;&lt;team&gt;@example.test">&quot;ada&quot;&lt;team&gt;@example.test</a>');
        });
    });
});
