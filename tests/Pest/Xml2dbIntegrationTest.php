<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/Xml2dbRunner.php';
require_once __DIR__ . '/Support/Xml2dbFixtures.php';

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Gidredirect;
use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Moffice;

/**
 * The hansard rows as stored, oldest item first.
 */
function xml2dbRows(): Collection {
    return Hansard::query()->orderBy('hdate')->orderBy('major')->orderBy('hpos')->toBase()->get();
}

/**
 * The member row with this id, as stored.
 */
function xml2dbMember(int $id): ?object {
    return Member::query()->where('member_id', $id)->toBase()->first();
}

/**
 * Backdates every modified column, so a later change to one stands out.
 */
function xml2dbBackdate(): void {
    Hansard::query()->toBase()->update(['modified' => '2000-01-01 00:00:00']);
    Epobject::query()->toBase()->update(['modified' => '2000-01-01 00:00:00']);
}

beforeEach(function () {
    $reason = Xml2dbRunner::unavailableReason();
    if ($reason !== null) {
        $this->markTestSkipped($reason);
    }
    $this->xml = new Xml2dbRunner();
    Xml2dbRunner::clearTables();
    $this->date = Xml2dbFixtures::DATE;
});

afterEach(function () {
    if (isset($this->xml)) {
        $this->xml->cleanup();
        Xml2dbRunner::clearTables();
    }
});

describe('xml2db.pl --debates', function () {
    describe('with one House day', function () {
        beforeEach(function () {
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day());
            $this->result = $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
            $this->rows = xml2dbRows();
        });

        it('exits cleanly', function () {
            expect($this->result['status'])->toBe(0);
        });

        it('stores an item for each element', function () {
            expect($this->rows)->toHaveCount(3);
        });

        it('types them heading, subheading, speech', function () {
            expect($this->rows->pluck('htype')->all())->toEqual([10, 11, 12]);
        });

        it('keeps the gids from the XML', function () {
            expect($this->rows->pluck('gid')->all())->toBe([
              "uk.org.publicwhip/debate/{$this->date}.1.1",
              "uk.org.publicwhip/debate/{$this->date}.1.2",
              "uk.org.publicwhip/debate/{$this->date}.1.3",
            ]);
        });

        it('marks every item as the House', function () {
            expect($this->rows->pluck('major')->unique()->values()->all())->toEqual([1]);
        });

        it('numbers the items in order', function () {
            expect($this->rows->pluck('hpos')->all())->toEqual([1, 2, 3]);
        });

        it('dates every item', function () {
            expect($this->rows->pluck('hdate')->unique()->values()->all())->toEqual([$this->date]);
        });

        it('pads a time to seconds', function () {
            expect($this->rows[2]->htime)->toBe('10:05:00');
        });

        it('keeps the source URL', function () {
            expect($this->rows[2]->source_url)->toBe("https://example.invalid/hansard/{$this->date}#3");
        });

        it('records the speaker\'s member id', function () {
            expect($this->rows[2]->speaker_id)->toEqual(10);
        });

        it('puts the subheading under the heading', function () {
            expect($this->rows[1]->section_id)->toEqual($this->rows[0]->epobject_id);
        });

        it('puts the speech under the heading', function () {
            expect($this->rows[2]->section_id)->toEqual($this->rows[0]->epobject_id);
        });

        it('puts the speech under the subheading', function () {
            expect($this->rows[2]->subsection_id)->toEqual($this->rows[1]->epobject_id);
        });

        it('stores the speech text', function () {
            expect(Epobject::find($this->rows[2]->epobject_id)->body)->toBe('<p>First words.</p>');
        });

        it('mixes the case of an all-capitals heading', function () {
            expect(Epobject::find($this->rows[0]->epobject_id)->body)->toBe('Treasury');
        });
    });

    describe('with a speech that has no speaker', function () {
        beforeEach(function () {
            $items = Xml2dbFixtures::majorHeading('representatives', $this->date, 'Business') .
              Xml2dbFixtures::speech('representatives', $this->date, 3, '<p>The House met.</p>', '');
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::wrap($items));
            $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
            $this->speech = xml2dbRows()[1];
        });

        it('types it as procedural', function () {
            expect($this->speech->htype)->toEqual(13);
        });

        it('gives it no speaker', function () {
            expect($this->speech->speaker_id)->toEqual(0);
        });
    });

    describe('with a speaker the parser could not match', function () {
        beforeEach(function () {
            $items = Xml2dbFixtures::majorHeading('representatives', $this->date, 'Business') .
              Xml2dbFixtures::speech('representatives', $this->date, 3, '<p>Hello.</p>', 'uk.org.publicwhip/member/unknown', 'Visitor Example');
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::wrap($items));
            $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
            $this->speech = xml2dbRows()[1];
        });

        it('gives it no speaker id', function () {
            expect($this->speech->speaker_id)->toEqual(0);
        });

        it('puts the speaker\'s name in the text', function () {
            expect(Epobject::find($this->speech->epobject_id)->body)->toBe('<p class="unknownspeaker">Visitor Example:</p> <p>Hello.</p>');
        });
    });

    describe('with accented text', function () {
        beforeEach(function () {
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day('representatives', $this->date, '<p>Caf&#233; &amp; more.</p>'));
            $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
        });

        it('stores it as numeric entities', function () {
            expect(Epobject::find(xml2dbRows()[2]->epobject_id)->body)->toBe('<p>Caf&#233; &amp; more.</p>');
        });
    });

    describe('with the proof attribute', function () {
        it('saves it', function (?string $attribute, ?int $stored) {
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day('representatives', $this->date, '<p>Words.</p>', $attribute));
            $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
            expect(xml2dbRows()->pluck('proof')->unique()->values()->all())->toEqual([$stored]);
        })->with([
          'proof' => ['1', 1],
          'final' => ['0', 0],
          'absent' => [null, null],
        ]);
    });

    describe('with a Senate day', function () {
        beforeEach(function () {
            $this->xml->writeDay('senate', $this->date, Xml2dbFixtures::day('senate'));
            $this->result = $this->xml->run(['--lordsdebates', "--date={$this->date}", '--cronquiet']);
            $this->rows = xml2dbRows();
        });

        it('exits cleanly', function () {
            expect($this->result['status'])->toBe(0);
        });

        it('stores an item for each element', function () {
            expect($this->rows)->toHaveCount(3);
        });

        it('marks every item as the Senate', function () {
            expect($this->rows->pluck('major')->unique()->values()->all())->toEqual([101]);
        });
    });

    describe('with both houses', function () {
        beforeEach(function () {
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day('representatives'));
            $this->xml->writeDay('senate', $this->date, Xml2dbFixtures::day('senate'));
            $this->xml->run(['--debates', '--lordsdebates', "--date={$this->date}", '--cronquiet']);
        });

        it('loads both', function () {
            expect(xml2dbRows()->pluck('major')->all())->toEqual([1, 1, 1, 101, 101, 101]);
        });
    });
});

describe('xml2db.pl date options', function () {
    beforeEach(function () {
        foreach (['2024-03-19', '2024-03-20', '2024-03-21'] as $day) {
            $this->xml->writeDay('representatives', $day, Xml2dbFixtures::day('representatives', $day));
        }
    });

    it('loads one day with --date', function () {
        $this->xml->run(['--debates', '--date=2024-03-20', '--cronquiet']);
        expect(xml2dbRows()->pluck('hdate')->unique()->values()->all())->toEqual(['2024-03-20']);
    });

    it('loads an inclusive range with --from and --to', function () {
        $this->xml->run(['--debates', '--from=2024-03-19', '--to=2024-03-20', '--cronquiet']);
        expect(xml2dbRows()->pluck('hdate')->unique()->values()->all())->toEqual(['2024-03-19', '2024-03-20']);
    });

    it('loads every day with --all', function () {
        $this->xml->run(['--debates', '--all', '--cronquiet']);
        expect(xml2dbRows())->toHaveCount(9);
    });

    it('loads nothing without a date option', function () {
        $this->xml->run(['--debates', '--cronquiet']);
        expect(xml2dbRows())->toHaveCount(0);
    });
});

describe('xml2db.pl loading a day again', function () {
    beforeEach(function () {
        $this->args = ['--debates', "--date={$this->date}", '--cronquiet'];
        $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day());
        $this->xml->run($this->args);
        $this->first = xml2dbRows();
        xml2dbBackdate();
    });

    describe('when nothing changed', function () {
        beforeEach(function () {
            $this->result = $this->xml->run($this->args);
        });

        it('keeps the same rows', function () {
            expect(xml2dbRows()->pluck('epobject_id')->all())->toEqual($this->first->pluck('epobject_id')->all());
        });

        it('leaves hansard.modified alone', function () {
            expect(xml2dbRows()->pluck('modified')->unique()->values()->all())->toEqual(['2000-01-01 00:00:00']);
        });

        it('leaves epobject.modified alone', function () {
            expect(Epobject::query()->toBase()->pluck('modified')->unique()->values()->all())->toEqual(['2000-01-01 00:00:00']);
        });

        it('reports no changes', function () {
            expect($this->result['output'])->not->toContain('updating');
        });
    });

    describe('when only the proof flag changed', function () {
        beforeEach(function () {
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day('representatives', $this->date, '<p>First words.</p>', '1'));
            $this->xml->run($this->args);
        });

        it('saves the new flag', function () {
            expect(xml2dbRows()->pluck('proof')->unique()->values()->all())->toEqual([1]);
        });

        it('leaves hansard.modified alone', function () {
            expect(xml2dbRows()->pluck('modified')->unique()->values()->all())->toEqual(['2000-01-01 00:00:00']);
        });
    });

    describe('when the speech text changed', function () {
        beforeEach(function () {
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day('representatives', $this->date, '<p>Corrected words.</p>'));
            $this->xml->run($this->args);
            $this->speech = Epobject::find($this->first[2]->epobject_id);
        });

        it('updates the text in place', function () {
            expect($this->speech->body)->toBe('<p>Corrected words.</p>');
        });

        it('keeps the row count', function () {
            expect(xml2dbRows())->toHaveCount(3);
        });

        it('bumps epobject.modified', function () {
            expect($this->speech->getRawOriginal('modified'))->not->toBe('2000-01-01 00:00:00');
        });
    });

    describe('when the time changed', function () {
        beforeEach(function () {
            $xml = str_replace('time="10:05"', 'time="10:30"', Xml2dbFixtures::day());
            $this->xml->writeDay('representatives', $this->date, $xml);
            $this->xml->run($this->args);
        });

        it('updates the time', function () {
            expect(xml2dbRows()[2]->htime)->toBe('10:30:00');
        });

        it('bumps hansard.modified', function () {
            expect(xml2dbRows()[2]->modified)->not->toBe('2000-01-01 00:00:00');
        });
    });

    describe('when an item has left the XML', function () {
        beforeEach(function () {
            $shorter = Xml2dbFixtures::wrap(
              Xml2dbFixtures::majorHeading('representatives', $this->date, 'TREASURY') .
              Xml2dbFixtures::minorHeading('representatives', $this->date, 'Fictional Bill 2024')
            );
            $this->xml->writeDay('representatives', $this->date, $shorter);
        });

        describe('without --force', function () {
            beforeEach(function () {
                $this->result = $this->xml->run($this->args);
            });

            it('stops with an error', function () {
                expect($this->result['status'])->not->toBe(0);
            });

            it('says how to delete it', function () {
                expect($this->result['output'])->toContain('not in XML, run again with --force to delete');
            });

            it('keeps the item', function () {
                expect(xml2dbRows())->toHaveCount(3);
            });
        });

        describe('with --force', function () {
            beforeEach(function () {
                $this->result = $this->xml->run([...$this->args, '--force']);
            });

            it('exits cleanly', function () {
                expect($this->result['status'])->toBe(0);
            });

            it('deletes the item', function () {
                expect(xml2dbRows())->toHaveCount(2);
            });

            it('deletes its text too', function () {
                expect(Epobject::query()->count())->toBe(2);
            });
        });
    });

    describe('when a gid is redirected', function () {
        beforeEach(function () {
            $old = Xml2dbFixtures::gid('representatives', $this->date, 1, 3);
            $new = Xml2dbFixtures::gid('representatives', $this->date, 1, 4);
            $items = Xml2dbFixtures::majorHeading('representatives', $this->date, 'TREASURY') .
              Xml2dbFixtures::minorHeading('representatives', $this->date, 'Fictional Bill 2024') .
              Xml2dbFixtures::speech('representatives', $this->date, 4, '<p>First words.</p>') .
              "<gidredirect oldgid=\"$old\" newgid=\"$new\" matchtype=\"exact\"/>\n";
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::wrap($items));
            $this->xml->run($this->args);
        });

        it('records the redirect', function () {
            expect(Gidredirect::query()->toBase()->get()->map(fn ($row) => [$row->gid_from, $row->gid_to])->all())->toBe([
              ["uk.org.publicwhip/debate/{$this->date}.1.3", "uk.org.publicwhip/debate/{$this->date}.1.4"],
            ]);
        });

        it('deletes the redirected item', function () {
            expect(xml2dbRows()->pluck('gid')->all())->not->toContain("uk.org.publicwhip/debate/{$this->date}.1.3");
        });

        it('keeps the item it points to', function () {
            expect(xml2dbRows()->pluck('gid')->all())->toContain("uk.org.publicwhip/debate/{$this->date}.1.4");
        });
    });
});

describe('xml2db.pl with XML it cannot load', function () {
    describe('with a speech before any heading', function () {
        beforeEach(function () {
            $speech = Xml2dbFixtures::speech('representatives', Xml2dbFixtures::DATE, 3, '<p>Words.</p>');
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::wrap($speech));
            $this->result = $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
        });

        it('stops with an error', function () {
            expect($this->result['status'])->not->toBe(0);
        });

        it('names the problem', function () {
            expect($this->result['output'])->toContain('speech without (sub)heading');
        });

        it('loads nothing', function () {
            expect(xml2dbRows())->toHaveCount(0);
        });
    });

    describe('with the same gid twice in one file', function () {
        beforeEach(function () {
            $heading = Xml2dbFixtures::majorHeading('representatives', $this->date, 'TREASURY');
            $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::wrap($heading . $heading));
            $this->result = $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
        });

        it('exits cleanly', function () {
            expect($this->result['status'])->toBe(0);
        });

        it('loads the item once', function () {
            expect(xml2dbRows())->toHaveCount(1);
        });
    });

    describe('with the same gid in two files for a day', function () {
        beforeEach(function () {
            $day = Xml2dbFixtures::day();
            $this->xml->writeDay('representatives', $this->date, $day);
            $this->xml->writeDay('representatives', $this->date, $day, 'b');
            $this->result = $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
        });

        it('exits cleanly', function () {
            expect($this->result['status'])->toBe(0);
        });

        it('loads each item once', function () {
            expect(xml2dbRows())->toHaveCount(3);
        });
    });
});

describe('xml2db.pl --members', function () {
    $person = '<people><person id="uk.org.publicwhip/person/10001"><office id="uk.org.publicwhip/member/1"/><office id="uk.org.publicwhip/moffice/1"/><office id="uk.org.publicwhip/moffice/2"/></person>' .
      '<person id="uk.org.publicwhip/person/10002"><office id="uk.org.publicwhip/lord/100001"/></person></people>';
    $representative = static fn (string $party = 'Example Party'): string => '<members><member division="Fictional Division" firstname="Alex" lastname="Example" fromdate="2010-01-01" todate="9999-12-31" fromwhy="general_election" towhy="still_in_office" house="representatives" id="uk.org.publicwhip/member/1" party="' . $party . '" title=""/></members>';
    $senator = '<members><member division="Tasmania" firstname="Sam" lastname="Sample" fromdate="2011-07-01" todate="9999-12-31" fromwhy="general_election" towhy="still_in_office" house="senate" id="uk.org.publicwhip/lord/100001" party="Sample Party" title=""/></members>';
    $minister = static fn (string $second = '2016-01-01'): string => '<ministers>' .
      '<ministerofficegroup><moffice dept="" fromdate="2015-01-01" id="uk.org.publicwhip/moffice/1" matchid="uk.org.publicwhip/member/1" name="Alex Example" position="Minister for Examples" source="" todate="2016-01-01"/></ministerofficegroup>' .
      '<ministerofficegroup><moffice dept="" fromdate="2016-01-01" id="uk.org.publicwhip/moffice/2" matchid="uk.org.publicwhip/member/1" name="Alex Example" position="Minister for Examples" source="" todate="' . $second . '"/></ministerofficegroup>' .
      '</ministers>';

    describe('with a representative, a senator and a minister', function () use ($person, $representative, $senator, $minister) {
        beforeEach(function () use ($person, $representative, $senator, $minister) {
            $this->xml->writeMembers(['people' => $person, 'representatives' => $representative(), 'senators' => $senator, 'ministers' => $minister()]);
            $this->result = $this->xml->run(['--members', '--all']);
        });

        it('exits cleanly', function () {
            expect($this->result['status'])->toBe(0);
        });

        it('stores the representative', function () {
            expect((array) xml2dbMember(1))->toMatchArray([
              'house' => 1,
              'first_name' => 'Alex',
              'last_name' => 'Example',
              'constituency' => 'Fictional Division',
              'party' => 'Example Party',
              'entered_house' => '2010-01-01',
              'left_house' => '9999-12-31',
              'entered_reason' => 'general_election',
              'left_reason' => 'still_in_office',
              'person_id' => 10001,
            ]);
        });

        it('stores the senator under house 2', function () {
            expect((array) xml2dbMember(100001))->toMatchArray([
              'house' => 2,
              'first_name' => 'Sam',
              'constituency' => 'Tasmania',
              'person_id' => 10002,
            ]);
        });

        it('joins consecutive offices into one', function () {
            expect(Moffice::query()->toBase()->get()->map(fn ($row) => [$row->position, $row->from_date, $row->to_date, $row->person])->all())->toEqual([
              ['Minister for Examples', '2015-01-01', '2016-01-01', 10001],
            ]);
        });
    });

    describe('with offices that are not consecutive', function () use ($person, $representative, $minister) {
        it('keeps them apart', function () use ($person, $representative, $minister) {
            $this->xml->writeMembers(['people' => $person, 'representatives' => $representative(), 'ministers' => str_replace('fromdate="2016-01-01"', 'fromdate="2017-01-01"', $minister('2018-01-01'))]);
            $this->xml->run(['--members', '--all']);
            expect(Moffice::query()->count())->toBe(2);
        });
    });

    describe('when loaded a second time', function () use ($person, $representative, $senator, $minister) {
        beforeEach(function () use ($person, $representative, $senator, $minister) {
            $this->files = ['people' => $person, 'representatives' => $representative(), 'senators' => $senator, 'ministers' => $minister()];
            $this->xml->writeMembers($this->files);
            $this->xml->run(['--members', '--all']);
        });

        it('does not duplicate the offices', function () {
            $this->xml->run(['--members', '--all']);
            expect(Moffice::query()->count())->toBe(1);
        });

        it('does not announce members again', function () {
            $result = $this->xml->run(['--members', '--all']);
            expect($result['output'])->not->toContain('Adding new member');
        });

        it('replaces a member whose details changed', function () use ($representative) {
            $this->xml->writeMembers(['representatives' => $representative('New Party')] + $this->files);
            $result = $this->xml->run(['--members', '--all']);
            expect($result['output'])->toContain('Replacing existing member with new data for 1');
        });

        it('saves the changed details', function () use ($representative) {
            $this->xml->writeMembers(['representatives' => $representative('New Party')] + $this->files);
            $this->xml->run(['--members', '--all']);
            expect(xml2dbMember(1)->party)->toBe('New Party');
        });

        it('reports a member that left the XML', function () {
            $this->xml->writeMembers(['representatives' => '<members/>'] + $this->files);
            $result = $this->xml->run(['--members', '--all']);
            expect($result['output'])->toContain('Member 1 in DB, not in XML');
        });
    });

    describe('with a member of an unknown house', function () use ($person, $representative) {
        it('stops with an error', function () use ($person, $representative) {
            $this->xml->writeMembers(['people' => $person, 'representatives' => str_replace('house="representatives"', 'house="elsewhere"', $representative())]);
            $result = $this->xml->run(['--members', '--all']);
            expect($result['output'])->toContain('Unknown house');
        });
    });
});
