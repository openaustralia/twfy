<?php

/**
 * @file
 * What a profile says about a person. Uses fictional members.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/MemberFixtures.php';
require_once __DIR__ . '/../../www/includes/utility.php';

use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Models\Consinfo;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Memberinfo;
use OpenAustralia\TWFY\Models\Moffice;
use OpenAustralia\TWFY\Models\Personinfo;
use OpenAustralia\TWFY\Parliament\MemberFacts;
use OpenAustralia\TWFY\Parliament\MemberLinks;
use OpenAustralia\TWFY\Parliament\MemberProfile;
use OpenAustralia\TWFY\Parliament\MemberStatistics;
use OpenAustralia\TWFY\Parliament\Person;
use OpenAustralia\TWFY\Parliament\RecentSpeeches;

uses(TransactionalTestCase::class);

/**
 * Adds a term for a fictional person, a sitting Senator unless told otherwise.
 *
 * @param int $member
 *   The member id.
 * @param array<string, int|string> $overrides
 *   Columns to change.
 */
function profile_term(int $member, array $overrides = []): Member {
    return MemberFixtures::term($member, $overrides + ['person_id' => 97301, 'house' => 2, 'first_name' => 'Kim', 'last_name' => 'Specimen', 'constituency' => 'Tasmania', 'entered_house' => '2022-07-01']);
}

/**
 * Adds an earlier term in the House, ending in 2019.
 */
function profile_house_term(int $member = 97302): Member {
    return profile_term($member, [
        'house' => 1,
        'constituency' => 'Testville',
        'entered_house' => '2016-07-02',
        'left_house' => '2019-04-11',
        'left_reason' => 'resigned',
    ]);
}

/**
 * The profile of the fictional person.
 */
function profile(): MemberProfile {
    return new MemberProfile(Person::find(97301));
}

/**
 * Stores one fact about the person.
 */
function profile_fact(string $key, string $value): void {
    Personinfo::create(['person_id' => 97301, 'data_key' => $key, 'data_value' => $value]);
}

/**
 * The person's numbers.
 */
function profile_statistics(): MemberStatistics {
    $person = Person::find(97301);
    return new MemberStatistics($person, MemberFacts::for($person));
}

describe('MemberProfile::pageTitle()', function () {
    describe('with a sitting Senator', function () {
        it('names them and their state', function () {
            profile_term(97301);

            expect(profile()->pageTitle())->toBe('Senator Kim Specimen, Tasmania');
        });
    });

    describe('with someone who has left both', function () {
        it('calls them former in each', function () {
            profile_term(97301, ['left_house' => '2025-06-30']);
            profile_house_term();

            expect(profile()->pageTitle())
              ->toBe('Kim Specimen, former Representative, Tasmania, former Senator, Tasmania');
        });
    });
});

describe('MemberProfile::heading()', function () {
    dataset('headings', [
        'a sitting Senator' => [2, '9999-12-31', 'Senator Kim Specimen'],
        'a sitting Representative' => [1, '9999-12-31', 'Kim Specimen MP'],
        'someone who has left' => [2, '2025-06-30', 'Kim Specimen'],
    ]);

    it('titles a sitting member', function (int $house, string $left, string $heading) {
        profile_term(97301, ['house' => $house, 'left_house' => $left]);

        expect(profile()->heading())->toBe($heading);
    })->with('headings');
});

describe('MemberProfile::formerly()', function () {
    describe('with a sitting Senator', function () {
        it('is empty', function () {
            profile_term(97301);

            expect(profile()->formerly())->toBeNull();
        });
    });

    describe('with a house they left', function () {
        it('names it', function () {
            profile_term(97301);
            profile_house_term();

            expect(profile()->formerly())->toBe('Former Representative');
        });
    });

    describe('with both houses left', function () {
        it('names both', function () {
            profile_term(97301, ['left_house' => '2025-06-30']);
            profile_house_term();

            expect(profile()->formerly())->toBe('Former Senator and Representative');
        });
    });
});

describe('MemberProfile::party()', function () {
    beforeEach(function () {
        profile_term(97301);
    });

    it('names the party', function () {
        expect(profile()->party())->toBe('Example Party');
    });

    describe('with a presiding officer', function () {
        it('adds the party they came from', function () {
            Member::find(97301)->update(['party' => 'PRES']);
            profile_term(97303, ['party' => 'Old Party', 'entered_house' => '2019-07-01', 'left_house' => '2022-07-01', 'left_reason' => 'changed_party']);

            expect(profile()->party())->toBe('President, and Old Party');
        });
    });
});

describe('MemberProfile::since()', function () {
    describe('with one house and party', function () {
        it('gives the start and reason', function () {
            profile_term(97301);

            expect(profile()->since())->toBe(['date' => '1 July 2022', 'reason' => 'Federal election']);
        });
    });

    describe('with a start known to the year', function () {
        it('gives only the year', function () {
            profile_term(97301, ['entered_house' => '1990-01-01']);

            expect(profile()->since()['date'])->toBe('1990');
        });
    });

    describe('with an unknown reason', function () {
        it('gives none', function () {
            profile_term(97301, ['entered_reason' => 'unknown']);

            expect(profile()->since()['reason'])->toBe('');
        });
    });

    describe('with someone who has left', function () {
        it('is empty', function () {
            profile_term(97301, ['left_house' => '2025-06-30']);

            expect(profile()->since())->toBeNull();
        });
    });

    describe('with a change of seat', function () {
        it('leaves it to the history', function () {
            profile_term(97301);
            profile_term(97303, ['constituency' => 'Victoria', 'entered_house' => '2019-07-01', 'left_house' => '2022-07-01']);

            expect(profile()->since())->toBeNull();
        });
    });

    describe('with a break in service', function () {
        it('leaves it to the history', function () {
            profile_term(97301);
            profile_term(97303, ['entered_house' => '2016-07-02', 'left_house' => '2019-04-11', 'left_reason' => 'resigned']);

            expect(profile()->since())->toBeNull();
        });
    });

    describe('with two houses', function () {
        it('leaves it to the history', function () {
            profile_term(97301);
            profile_house_term();

            expect(profile()->since())->toBeNull();
        });
    });

    describe('with a change of party', function () {
        it('leaves it to the history', function () {
            profile_term(97301);
            profile_term(97303, ['party' => 'Old Party', 'entered_house' => '2019-07-01', 'left_house' => '2022-07-01', 'left_reason' => 'changed_party']);

            expect(profile()->since())->toBeNull();
        });
    });

    describe('with a presiding officer', function () {
        it('still gives the start', function () {
            profile_term(97301, ['party' => 'PRES']);
            profile_term(97303, ['entered_house' => '2019-07-01', 'left_house' => '2022-07-01', 'left_reason' => 'changed_party']);

            expect(profile()->since()['date'])->toBe('1 July 2019');
        });
    });
});

describe('MemberProfile offices', function () {
    beforeEach(function () {
        profile_term(97301);
        Moffice::create(['person' => 97301, 'position' => 'Minister for Examples', 'dept' => '', 'from_date' => '2022-06-01', 'to_date' => '9999-12-31', 'source' => '']);
        Moffice::create(['person' => 97301, 'position' => 'Old Office', 'dept' => '', 'from_date' => '2019-06-01', 'to_date' => '2022-06-01', 'source' => '']);
    });

    it('dates the current ones', function () {
        expect(profile()->currentRoles())->toBe([['title' => 'Minister for Examples', 'since' => '1 Jun 2022']]);
    });

    it('spans the past ones', function () {
        expect(profile()->pastOffices())
          ->toBe([['title' => 'Old Office', 'dates' => '1 Jun 2019 to 1 Jun 2022', 'house' => null]]);
    });

    dataset('titles naming a house', [
        'the Senate' => ['Leader in the Senate', House::Senate],
        'the House' => ['Whip in the House', House::Representatives],
        'neither' => ['Treasurer', null],
    ]);

    describe('with someone from both houses', function () {
        it('tags the house a title names', function (string $title, ?House $house) {
            profile_house_term();
            Moffice::query()->where('position', 'Old Office')->update(['position' => $title]);

            expect(profile()->pastOffices()[0]['house'])->toBe($house);
        })->with('titles naming a house');
    });
});

describe('MemberProfile neighbours', function () {
    beforeEach(function () {
        profile_house_term(97301);
        profile_term(97310, ['person_id' => 97310, 'house' => 1, 'constituency' => 'Testville', 'entered_house' => '2001-11-10', 'left_house' => '2016-05-09']);
        profile_term(97320, ['person_id' => 97320, 'house' => 1, 'constituency' => 'Testville', 'entered_house' => '2019-05-18']);
    });

    it('finds who came before', function () {
        expect(profile()->predecessors()->pluck('person_id')->all())->toBe([97310]);
    });

    it('finds who came after', function () {
        expect(profile()->successors()->pluck('person_id')->all())->toBe([97320]);
    });

    describe('with a later Senate term', function () {
        it('lists nobody', function () {
            profile_term(97330);

            expect(profile()->predecessors()->all())->toBe([]);
        });
    });
});

describe('MemberFacts::get()', function () {
    beforeEach(function () {
        profile_term(97301);
        Memberinfo::create(['member_id' => 97301, 'data_key' => 'spec_key', 'data_value' => 'from the term']);
    });

    it('reads a fact about the term', function () {
        expect(MemberFacts::for(Person::find(97301))->get('spec_key'))->toBe('from the term');
    });

    describe('with the same key for the seat', function () {
        it('prefers the constituency', function () {
            Consinfo::create(['constituency' => 'Tasmania', 'data_key' => 'spec_key', 'data_value' => 'from the seat']);

            expect(MemberFacts::for(Person::find(97301))->get('spec_key'))->toBe('from the seat');
        });
    });

    describe('with a stored HTML entity', function () {
        it('gives the character', function () {
            profile_fact('mp_website', 'https://example.test/?a=1&amp;b=2');

            expect(MemberFacts::for(Person::find(97301))->get('mp_website'))->toBe('https://example.test/?a=1&b=2');
        });
    });
});

describe('MemberLinks::for()', function () {
    beforeEach(function () {
        profile_term(97301);
        profile_fact('mp_contact_form', 'https://example.test/contact');
    });

    it('offers the contact form', function () {
        expect(array_column(MemberLinks::for(profile(), MemberFacts::for(Person::find(97301))), 'text'))
          ->toBe(['Contact form']);
    });

    describe('with an email address', function () {
        it('offers that instead', function () {
            profile_fact('mp_email', 'kim@example.test');

            expect(array_column(MemberLinks::for(profile(), MemberFacts::for(Person::find(97301))), 'url'))
              ->toBe(['mailto:kim@example.test']);
        });
    });
});

describe('MemberStatistics', function () {
    beforeEach(function () {
        profile_term(97301);
    });

    it('compares debates by quintile', function () {
        profile_fact('Ldebate_sectionsspoken_inlastyear', '1');
        profile_fact('Ldebate_sectionsspoken_inlastyear_quintile', '1');

        expect(profile_statistics()->debates())
          ->toBe([['count' => 1, 'standing' => 'above average amongst Senators']]);
    });

    it('compares alliterations by rank', function () {
        profile_fact('Lthree_word_alliterations', '4');
        profile_fact('Lthree_word_alliterations_rank', '2');
        profile_fact('Lthree_word_alliterations_rank_outof', '76');

        expect(profile_statistics()->alliterations()[0]['standing'])->toBe('2nd out of 76 Senators');
    });

    it('gives a reading age range', function () {
        profile_fact('reading_ease', '51.234');
        profile_fact('reading_year', '11.6');

        expect(profile_statistics()->readingAge())->toBe('16–17');
    });

    it('counts confirmed alerts only', function () {
        Alert::create(['email' => 'a@example.test', 'criteria' => 'speaker:97301', 'confirmed' => true, 'deleted' => false, 'registrationtoken' => 'x']);
        Alert::create(['email' => 'b@example.test', 'criteria' => 'speaker:97301', 'confirmed' => false, 'deleted' => false, 'registrationtoken' => 'y']);

        expect(profile_statistics()->followers())->toBe(1);
    });
});

describe('RecentSpeeches::for()', function () {
    it('tidies each search row', function () {
        $speeches = new class() extends RecentSpeeches {

            protected function search(int $personId): array {
                return [['listurl' => '/senate/?id=1&amp;s=x', 'hdate' => '2026-05-14', 'body' => 'Hello', 'parent' => ['body' => 'Motions']]];
            }

        };

        expect($speeches->for(97301))
          ->toBe([['url' => '/senate/?id=1&s=x', 'title' => 'Motions', 'date' => '14 May 2026', 'iso' => '2026-05-14', 'extract' => 'Hello']]);
    });

    describe('when the search fails', function () {
        it('gives null', function () {
            twfy_laravel();
            $speeches = new class() extends RecentSpeeches {

                protected function search(int $personId): array {
                    throw new RuntimeException('No search index (expected by this spec).');
                }

            };

            expect($speeches->for(97301))->toBeNull();
        });
    });
});
