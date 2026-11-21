<?php

/**
 * @file
 * Recording and clearing the errors from importing a day of Hansard. Uses fictional messages and dates.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use OpenAustralia\TWFY\ImportErrors;
use OpenAustralia\TWFY\Models\ImportError;

uses(TransactionalTestCase::class);

describe('ImportErrors::record()', function () {
    it('saves an error for a day', function () {
        ImportErrors::record('2026-02-03', 101, 'parser', 'Unknown speaker "The"');

        expect(ImportError::where('hdate', '2026-02-03')->value('message'))->toBe('Unknown speaker "The"');
    });

    it('keeps several errors for one day', function () {
        ImportErrors::record('2026-02-03', 101, 'parser', 'First');
        ImportErrors::record('2026-02-03', 101, 'parser', 'Second');

        expect(ImportError::where('hdate', '2026-02-03')->count())->toBe(2);
    });

    it('cuts a very long message', function () {
        ImportErrors::record('2026-02-03', 1, 'loader', str_repeat('x', 6000));

        expect(strlen(ImportError::first()->message))->toBe(5000);
    });
});

describe('ImportErrors::clear()', function () {
    beforeEach(function () {
        ImportErrors::record('2026-02-03', 1, 'parser', 'Parser error');
        ImportErrors::record('2026-02-03', 1, 'loader', 'Loader error');
        ImportErrors::record('2026-02-03', 101, 'parser', 'Senate error');
        ImportErrors::record('2026-02-04', 1, 'parser', 'Next day error');
    });

    it('clears a stage\'s errors for a day and house', function () {
        expect(ImportErrors::clear('2026-02-03', 1, 'parser'))->toBe(1);
    });

    it('leaves the other stage\'s errors', function () {
        ImportErrors::clear('2026-02-03', 1, 'parser');

        expect(ImportError::where('hdate', '2026-02-03')->where('major', 1)->value('source'))->toBe('loader');
    });

    it('leaves the other house\'s errors', function () {
        ImportErrors::clear('2026-02-03', 1, 'parser');

        expect(ImportError::where('major', 101)->count())->toBe(1);
    });

    it('leaves other days\' errors', function () {
        ImportErrors::clear('2026-02-03', 1, 'parser');

        expect(ImportError::where('hdate', '2026-02-04')->count())->toBe(1);
    });

    it('clears nothing for a day with no errors', function () {
        expect(ImportErrors::clear('2026-03-01', 1, 'parser'))->toBe(0);
    });
});

describe('ImportErrors::execute()', function () {
    it('records an error from its options', function () {
        $result = ImportErrors::execute('record', ['date' => '2026-02-03', 'house' => 'senate', 'source' => 'parser', 'message' => 'Bad']);

        expect([$result, ImportError::where('major', 101)->count()])->toBe([[0, ''], 1]);
    });

    it('clears errors from its options', function () {
        ImportErrors::record('2026-02-03', 101, 'parser', 'Bad');
        ImportErrors::execute('clear', ['date' => '2026-02-03', 'house' => 'senate', 'source' => 'parser']);

        expect(ImportError::count())->toBe(0);
    });

    it('uses the last of a repeated option', function () {
        ImportErrors::execute('record', ['date' => '2026-02-03', 'house' => 'senate', 'source' => 'parser', 'message' => ['One', 'Two']]);

        expect(ImportError::first()->message)->toBe('Two');
    });

    it('says what is missing', function () {
        expect(ImportErrors::execute('record', ['date' => '2026-02-03', 'house' => 'senate', 'source' => 'parser']))->toBe([2, 'Missing --message']);
    });

    it('does not need a message to clear', function () {
        expect(ImportErrors::execute('clear', ['date' => '2026-02-03', 'house' => 'senate', 'source' => 'parser']))->toBe([0, '']);
    });

    it('refuses a date that is not real', function () {
        expect(ImportErrors::execute('clear', ['date' => '2026-02-30', 'house' => 'senate', 'source' => 'parser'])[0])->toBe(2);
    });

    it('refuses a date in the wrong form', function () {
        expect(ImportErrors::execute('clear', ['date' => '3 Feb 2026', 'house' => 'senate', 'source' => 'parser'])[1])->toContain('YYYY-MM-DD');
    });

    it('refuses an unknown house', function () {
        expect(ImportErrors::execute('clear', ['date' => '2026-02-03', 'house' => 'lords', 'source' => 'parser'])[1])->toContain('representatives, senate');
    });

    it('refuses an unknown source', function () {
        expect(ImportErrors::execute('clear', ['date' => '2026-02-03', 'house' => 'senate', 'source' => 'cron'])[1])->toContain('parser, loader');
    });

    it('treats no options as missing', function () {
        expect(ImportErrors::execute('record', false))->toBe([2, 'Missing --date']);
    });

    it('writes nothing for a refused call', function () {
        ImportErrors::execute('record', ['date' => 'nope', 'house' => 'senate', 'source' => 'parser', 'message' => 'Bad']);

        expect(ImportError::count())->toBe(0);
    });
});

describe('ImportErrors::execute() when the database fails', function () {
    beforeEach(function () {
        $this->resolver = Model::getConnectionResolver();
        // A real MySQL connector pointed at a closed port, so it fails as an outage does.
        $down = new Connection(fn () => (new MySqlConnector())->connect([
          'driver' => 'mysql',
          'host' => '127.0.0.1',
          'port' => 1,
          'database' => 'twfy',
          'username' => 'nobody',
          'password' => '',
        ]));
        $resolver = new ConnectionResolver(['down' => $down]);
        $resolver->setDefaultConnection('down');
        Model::setConnectionResolver($resolver);
        $this->call = ['date' => '2026-02-03', 'house' => 'senate', 'source' => 'parser', 'message' => 'Bad'];
    });

    afterEach(function () {
        Model::setConnectionResolver($this->resolver);
    });

    it('fails with status 1, not a crash, when recording', function () {
        expect(ImportErrors::execute('record', $this->call)[0])->toBe(1);
    });

    it('fails with status 1, not a crash, when clearing', function () {
        expect(ImportErrors::execute('clear', $this->call)[0])->toBe(1);
    });

    it('says what it could not do', function () {
        expect(ImportErrors::execute('record', $this->call)[1])->toContain('Could not record the import error');
    });

    it('says what it could not do when clearing', function () {
        expect(ImportErrors::execute('clear', $this->call)[1])->toContain('Could not clear the import error: ');
    });
});

describe('ImportErrors::pattern()', function () {
    it('groups the same kind of error together', function (string $message) {
        expect(ImportErrors::pattern($message))->toBe("Incorrect time value: ? for column ?");
    })->with([
        'McEwen' => ["Incorrect time value: ' (McEwen) (16:29):' for column 'htime'"],
        'Curtin' => ["Incorrect time value: ' (Curtin) (15:03):' for column 'htime'"],
    ]);

    it('takes out numbers and bracketed names', function () {
        expect(ImportErrors::pattern('2026-02-03 senate: Found person (Stuart Robert) at line 718'))->toBe('0-0-0 senate: Found person (?) at line 0');
    });
});
