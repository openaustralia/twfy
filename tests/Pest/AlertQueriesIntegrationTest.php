<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/utility.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use OpenAustralia\TWFY\Models\Alert as AlertModel;

uses(TransactionalTestCase::class);

/**
 * The addresses in a fetch() result that belong to these specs.
 *
 * @param array<string, mixed> $result
 *   What ALERT::fetch() returned.
 *
 * @return list<string>
 *   The fictional addresses, in the order returned.
 */
function fetched_emails(array $result): array {
    $emails = array_column($result['data'], 'email');
    return array_values(array_filter($emails, fn ($email) => str_ends_with($email, '@example.invalid')));
}

/**
 * What suggest_alerts() printed.
 */
function suggestions_for(string $email, string $criteria, int $max = 5): string {
    ob_start();
    suggest_alerts($email, $criteria, $max);
    return ob_get_clean();
}

describe('ALERT::fetch()', function () {
    beforeEach(function () {
        AlertFixtures::alert('b-live@example.invalid', 'speaker:10001');
        AlertFixtures::alert('a-live@example.invalid', 'speaker:10001');
        AlertFixtures::alert('gone@example.invalid', 'speaker:10001', true, true);
        AlertFixtures::alert('new@example.invalid', 'speaker:10001', false);
    });

    describe('with confirmed and not deleted', function () {
        it('returns the live alerts by email', function () {
            expect(fetched_emails((new ALERT())->fetch(1, 0)))->toBe(['a-live@example.invalid', 'b-live@example.invalid']);
        });

        it('labels the result', function () {
            expect((new ALERT())->fetch(1, 0)['info'])->toBe('Alert');
        });

        it('returns each alert with the columns the mailer reads', function () {
            $row = (new ALERT())->fetch(1, 0)['data'][0];

            expect(array_keys($row))->toEqualCanonicalizing(['alert_id', 'email', 'criteria', 'registrationtoken', 'deleted', 'confirmed']);
        });
    });

    describe('with deleted alerts', function () {
        it('returns the deleted alerts', function () {
            expect(fetched_emails((new ALERT())->fetch(1, 1)))->toBe(['gone@example.invalid']);
        });
    });

    describe('with unconfirmed alerts', function () {
        it('returns the unconfirmed alerts', function () {
            expect(fetched_emails((new ALERT())->fetch(0, 0)))->toBe(['new@example.invalid']);
        });
    });

    describe('with SQL in an argument', function () {
        it('treats it as a value', function () {
            expect(fetched_emails((new ALERT())->fetch('1 OR 1=1', 0)))->toBe(['a-live@example.invalid', 'b-live@example.invalid']);
        });

        it('leaves the table alone', function () {
            (new ALERT())->fetch('1; DROP TABLE alerts', 0);

            expect(AlertModel::count())->toBeGreaterThanOrEqual(4);
        });
    });
});

describe('ALERT::listalerts()', function () {
    it('lists without error', function () {
        expect((new ALERT())->listalerts())->toBeNull();
    });
});

describe('ALERT::email_exists()', function () {
    beforeEach(function () {
        AlertFixtures::alert('alice@example.invalid', 'speaker:10001');
    });

    it('finds an address with an alert', function () {
        expect((new ALERT())->email_exists('alice@example.invalid'))->toBeTrue();
    });

    it('does not find an unknown address', function () {
        expect((new ALERT())->email_exists('nobody@example.invalid'))->toBeFalse();
    });

    it('does not find an empty address', function () {
        expect((new ALERT())->email_exists(''))->toBeFalse();
    });
});

describe('ALERT::confirm()', function () {
    beforeEach(function () {
        $this->pending = AlertFixtures::alert('alice@example.invalid', 'budget', false, true, 'secrettoken');
    });

    describe('with a valid token', function () {
        it('returns true', function () {
            expect((new ALERT())->confirm($this->pending->alert_id . '-secrettoken'))->toBeTrue();
        });

        it('confirms the alert', function () {
            (new ALERT())->confirm($this->pending->alert_id . '-secrettoken');

            expect($this->pending->fresh()->confirmed)->toBeTrue();
        });

        it('undeletes the alert', function () {
            (new ALERT())->confirm($this->pending->alert_id . '-secrettoken');

            expect($this->pending->fresh()->deleted)->toBeFalse();
        });

        it('remembers the address', function () {
            $alert = new ALERT();
            $alert->confirm($this->pending->alert_id . '-secrettoken');

            expect($alert->email)->toBe('alice@example.invalid');
        });

        it('remembers the criteria', function () {
            $alert = new ALERT();
            $alert->confirm($this->pending->alert_id . '-secrettoken');

            expect($alert->criteria)->toBe('budget');
        });

        it('flags the object confirmed', function () {
            $alert = new ALERT();
            $alert->confirm($this->pending->alert_id . '-secrettoken');

            expect($alert->confirmed)->toBeTrue();
        });
    });

    describe('with the older :: separator', function () {
        it('returns true', function () {
            expect((new ALERT())->confirm($this->pending->alert_id . '::secrettoken'))->toBeTrue();
        });
    });

    describe('with a token that does not match', function () {
        it('returns false', function () {
            expect((new ALERT())->confirm($this->pending->alert_id . '-wrongtoken'))->toBeFalse();
        });

        it('leaves the alert unconfirmed', function () {
            (new ALERT())->confirm($this->pending->alert_id . '-wrongtoken');

            expect($this->pending->fresh()->confirmed)->toBeFalse();
        });
    });

    describe('with a malformed token', function () {
        it('returns false', function (string $token) {
            expect((new ALERT())->confirm($token))->toBeFalse();
        })->with([
            'no separator' => 'abc',
            'too many parts' => '1-a-b',
            'a non-numeric id' => 'x-secrettoken',
            'an empty token' => '1-',
        ]);
    });
});

describe('suggest_alerts()', function () {
    beforeEach(function () {
        AlertFixtures::alert('searcher@example.invalid', 'speaker:10001');
        AlertFixtures::alert('other@example.invalid', 'speaker:10001');
        AlertFixtures::alert('other@example.invalid', 'speaker:10002');
    });

    describe('when followers also follow someone else', function () {
        it('introduces the suggestions', function () {
            expect(suggestions_for('searcher@example.invalid', 'speaker:10001'))->toContain('You may also be interested');
        });

        it('offers no link when only one follower does', function () {
            expect(suggestions_for('searcher@example.invalid', 'speaker:10001'))->not->toContain('Email me whenever');
        });
    });

    describe('when two followers both follow someone else', function () {
        beforeEach(function () {
            AlertFixtures::alert('third@example.invalid', 'speaker:10001');
            AlertFixtures::alert('third@example.invalid', 'speaker:10002');
            AlertFixtures::member(10002, 'Sam', 'Sample');
        });

        it('links to an alert for that person', function () {
            expect(suggestions_for('searcher@example.invalid', 'speaker:10001'))->toContain('pid=10002');
        });

        it('names the person', function () {
            expect(suggestions_for('searcher@example.invalid', 'speaker:10001'))->toContain('Sam Sample');
        });

        it('offers no link when the cap is zero', function () {
            expect(suggestions_for('searcher@example.invalid', 'speaker:10001', 0))->not->toContain('Email me whenever');
        });
    });

    describe('when the searcher already follows them', function () {
        it('suggests nothing', function () {
            AlertFixtures::alert('searcher@example.invalid', 'speaker:10002');

            expect(suggestions_for('searcher@example.invalid', 'speaker:10001'))->toBe('');
        });
    });

    describe('with keyword criteria', function () {
        it('suggests nothing', function () {
            expect(suggestions_for('searcher@example.invalid', 'budget'))->toBe('');
        });
    });
});
