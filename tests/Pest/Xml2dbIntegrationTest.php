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

/**
 * A suffix naming the loader being described, so that same-named groups for different loaders keep their own hooks.
 */
function xml2dbLabel(): string {
    return ' [' . $GLOBALS['xml2dbImplementation'] . ']';
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

// Every spec runs against both loaders, which have to behave the same.
foreach (['perl', 'php'] as $implementation) {
    $GLOBALS['xml2dbImplementation'] = $implementation;
    describe($implementation, function () use ($implementation) {
        beforeEach(function () use ($implementation) {
            $this->xml->implementation = $implementation;
        });

        describe('xml2db.pl --debates' . xml2dbLabel(), function () {
            describe('with one House day' . xml2dbLabel(), function () {
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

            describe('with a speech that has no speaker' . xml2dbLabel(), function () {
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

            describe('with a speaker the parser could not match' . xml2dbLabel(), function () {
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

            describe('with accented text' . xml2dbLabel(), function () {
                beforeEach(function () {
                    $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day('representatives', $this->date, '<p>Caf&#233; &amp; more.</p>'));
                    $this->xml->run(['--debates', "--date={$this->date}", '--cronquiet']);
                });

                it('stores it as numeric entities', function () {
                    expect(Epobject::find(xml2dbRows()[2]->epobject_id)->body)->toBe('<p>Caf&#233; &amp; more.</p>');
                });
            });

            describe('with the proof attribute' . xml2dbLabel(), function () {
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

            describe('with a Senate day' . xml2dbLabel(), function () {
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

            describe('with both houses' . xml2dbLabel(), function () {
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

        describe('xml2db.pl date options' . xml2dbLabel(), function () {
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

        describe('xml2db.pl loading a day again' . xml2dbLabel(), function () {
            beforeEach(function () {
                $this->args = ['--debates', "--date={$this->date}", '--cronquiet'];
                $this->xml->writeDay('representatives', $this->date, Xml2dbFixtures::day());
                $this->xml->run($this->args);
                $this->first = xml2dbRows();
                xml2dbBackdate();
            });

            describe('when nothing changed' . xml2dbLabel(), function () {
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

            describe('when only the proof flag changed' . xml2dbLabel(), function () {
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

            describe('when the speech text changed' . xml2dbLabel(), function () {
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

            describe('when the time changed' . xml2dbLabel(), function () {
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

            describe('when an item has left the XML' . xml2dbLabel(), function () {
                beforeEach(function () {
                    $shorter = Xml2dbFixtures::wrap(
                      Xml2dbFixtures::majorHeading('representatives', $this->date, 'TREASURY') .
                      Xml2dbFixtures::minorHeading('representatives', $this->date, 'Fictional Bill 2024')
                    );
                    $this->xml->writeDay('representatives', $this->date, $shorter);
                });

                describe('without --force' . xml2dbLabel(), function () {
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

                describe('with --force' . xml2dbLabel(), function () {
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

            describe('when a gid is redirected' . xml2dbLabel(), function () {
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

        describe('xml2db.pl with XML it cannot load' . xml2dbLabel(), function () {
            describe('with a speech before any heading' . xml2dbLabel(), function () {
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

            describe('with the same gid twice in one file' . xml2dbLabel(), function () {
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

            describe('with the same gid in two files for a day' . xml2dbLabel(), function () {
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

        describe('xml2db.pl --members' . xml2dbLabel(), function () {
            $person = '<people><person id="uk.org.publicwhip/person/10001"><office id="uk.org.publicwhip/member/1"/><office id="uk.org.publicwhip/moffice/1"/><office id="uk.org.publicwhip/moffice/2"/></person>' .
              '<person id="uk.org.publicwhip/person/10002"><office id="uk.org.publicwhip/lord/100001"/></person></people>';
            $representative = static fn (string $party = 'Example Party'): string => '<members><member division="Fictional Division" firstname="Alex" lastname="Example" fromdate="2010-01-01" todate="9999-12-31" fromwhy="general_election" towhy="still_in_office" house="representatives" id="uk.org.publicwhip/member/1" party="' . $party . '" title=""/></members>';
            $senator = '<members><member division="Tasmania" firstname="Sam" lastname="Sample" fromdate="2011-07-01" todate="9999-12-31" fromwhy="general_election" towhy="still_in_office" house="senate" id="uk.org.publicwhip/lord/100001" party="Sample Party" title=""/></members>';
            $minister = static fn (string $second = '2016-01-01'): string => '<ministers>' .
              '<ministerofficegroup><moffice dept="" fromdate="2015-01-01" id="uk.org.publicwhip/moffice/1" matchid="uk.org.publicwhip/member/1" name="Alex Example" position="Minister for Examples" source="" todate="2016-01-01"/></ministerofficegroup>' .
              '<ministerofficegroup><moffice dept="" fromdate="2016-01-01" id="uk.org.publicwhip/moffice/2" matchid="uk.org.publicwhip/member/1" name="Alex Example" position="Minister for Examples" source="" todate="' . $second . '"/></ministerofficegroup>' .
              '</ministers>';

            describe('with a representative, a senator and a minister' . xml2dbLabel(), function () use ($person, $representative, $senator, $minister) {
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

            describe('with offices that are not consecutive' . xml2dbLabel(), function () use ($person, $representative, $minister) {
                it('keeps them apart', function () use ($person, $representative, $minister) {
                    $this->xml->writeMembers(['people' => $person, 'representatives' => $representative(), 'ministers' => str_replace('fromdate="2016-01-01"', 'fromdate="2017-01-01"', $minister('2018-01-01'))]);
                    $this->xml->run(['--members', '--all']);
                    expect(Moffice::query()->count())->toBe(2);
                });
            });

            describe('when loaded a second time' . xml2dbLabel(), function () use ($person, $representative, $senator, $minister) {
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

            describe('with a member of an unknown house' . xml2dbLabel(), function () use ($person, $representative) {
                it('stops with an error', function () use ($person, $representative) {
                    $this->xml->writeMembers(['people' => $person, 'representatives' => str_replace('house="representatives"', 'house="elsewhere"', $representative())]);
                    $result = $this->xml->run(['--members', '--all']);
                    expect($result['output'])->toContain('Unknown house');
                });
            });
        });
    });
}

/**
 * Everything the loaders wrote, without ids or timestamps, so two loads can be compared.
 *
 * Rows are keyed by gid, and the section links are translated from epobject ids to gids.
 *
 * @return array<string, mixed>
 *   The rows of each table.
 */
function xml2dbSnapshot(): array {
    $gids = Hansard::query()->toBase()->pluck('gid', 'epobject_id')->all();
    $texts = Epobject::query()->toBase()->get()->keyBy('epobject_id');
    $hansard = [];
    foreach (Hansard::query()->toBase()->orderBy('gid')->get() as $row) {
        $text = $texts[$row->epobject_id];
        $hansard[$row->gid] = [
            'htype' => $row->htype,
'speaker_id' => $row->speaker_id,
'major' => $row->major,
'minor' => $row->minor,
            'section' => $gids[$row->section_id] ?? $row->section_id,
            'subsection' => $gids[$row->subsection_id] ?? $row->subsection_id,
            'hpos' => $row->hpos,
'hdate' => $row->hdate,
'htime' => $row->htime,
'source_url' => $row->source_url,
            'proof' => $row->proof,
'title' => $text->title,
'body' => $text->body,
'type' => $text->type,
        ];
    }
    $members = Member::query()->orderBy('member_id')->toBase()->get()->map(
        fn ($row) => array_diff_key((array) $row, ['lastupdate' => 1, 'created_at' => 1, 'updated_at' => 1])
    )->all();
    $offices = Moffice::query()->orderBy('moffice_id')->toBase()->get()->map(
        fn ($row) => array_diff_key((array) $row, ['moffice_id' => 1, 'created_at' => 1, 'updated_at' => 1])
    )->all();
    return [
        'hansard' => $hansard,
        'epobjects' => Epobject::query()->count(),
        'redirects' => Gidredirect::query()->orderBy('gid_from')->toBase()->get()->map(
            fn ($row) => [$row->gid_from, $row->gid_to, $row->hdate, $row->major]
        )->all(),
        'members' => $members,
        'offices' => $offices,
    ];
}

/**
 * The scenarios the two loaders have to handle the same way. Each writes its files and runs the loader,
 * returning whether each run succeeded.
 *
 * @return array<string, array{Closure(Xml2dbRunner): list<bool>}>
 *   Scenarios by name.
 */
function xml2dbScenarios(): array {
    $date = Xml2dbFixtures::DATE;
    $debates = ['--debates', '--lordsdebates', "--date=$date", '--cronquiet'];
    $house = ['--debates', "--date=$date", '--cronquiet'];
    $succeeded = static fn (array ...$runs): array => array_map(static fn (array $run): bool => $run['status'] === 0, $runs);
    $people = '<people><person id="uk.org.publicwhip/person/10001"><office id="uk.org.publicwhip/member/1"/><office id="uk.org.publicwhip/moffice/1"/><office id="uk.org.publicwhip/moffice/2"/></person>' .
        '<person id="uk.org.publicwhip/person/10002"><office id="uk.org.publicwhip/lord/100001"/></person></people>';
    $members = static fn (string $party): array => [
        'people' => $people,
        'representatives' => '<members><member division="O\'Brien &amp; Sons Division" firstname="Zo&#235;" lastname="M&#252;ller" fromdate="2010-01-01" todate="9999-12-31" fromwhy="general_election" towhy="still_in_office" house="representatives" id="uk.org.publicwhip/member/1" party="' . $party . '" title="Dr"/></members>',
        'senators' => '<members><member division="Tasmania" firstname="Sam" lastname="Sample" fromdate="2011-07-01" todate="9999-12-31" fromwhy="general_election" towhy="still_in_office" house="senate" id="uk.org.publicwhip/lord/100001" party="Sample Party" title=""/></members>',
        'ministers' => '<ministers><ministerofficegroup><moffice dept="Treasury &amp; Finance" fromdate="2015-01-01" id="uk.org.publicwhip/moffice/1" matchid="uk.org.publicwhip/member/1" name="Zo&#235; M&#252;ller" position="Minister for Examples" responsibility="Fictional Affairs" source="" todate="2016-01-01"/></ministerofficegroup><ministerofficegroup><moffice dept="Treasury &amp; Finance" fromdate="2016-01-01" id="uk.org.publicwhip/moffice/2" matchid="uk.org.publicwhip/member/1" name="Zo&#235; M&#252;ller" position="Minister for Examples" responsibility="Fictional Affairs" source="" todate="2017-01-01"/></ministerofficegroup></ministers>',
    ];

    return [
        'a House day' => [function (Xml2dbRunner $xml) use ($succeeded, $house, $date) {
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day());
            return $succeeded($xml->run($house));
        }
],
        'both houses' => [function (Xml2dbRunner $xml) use ($succeeded, $debates, $date) {
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day('representatives'));
            $xml->writeDay('senate', $date, Xml2dbFixtures::day('senate', $date, '<p>Senate <i>words</i> &amp; <a href="https://example.invalid/?a=1&amp;b=2" title="T">link</a>.</p>', '1'));
            return $succeeded($xml->run($debates));
        }
],
        'unknown speaker, accents, empty elements' => [function (Xml2dbRunner $xml) use ($succeeded, $house, $date) {
            $items = Xml2dbFixtures::majorHeading('representatives', $date, 'BILLS &amp; MOTIONS') .
                Xml2dbFixtures::minorHeading('representatives', $date, 'Caf&#233; Bill 2024') .
                Xml2dbFixtures::speech('representatives', $date, 3, "<p>Caf&#233; &amp; more &lt; less > none.</p>\n  <p></p><p/>\n<ul></ul>", 'uk.org.publicwhip/member/unknown', 'Visitor Example') .
                Xml2dbFixtures::speech('representatives', $date, 4, '<p>No speaker.</p>', '');
            $xml->writeDay('representatives', $date, Xml2dbFixtures::wrap($items, null));
            return $succeeded($xml->run($house));
        }
],
        'a date range' => [function (Xml2dbRunner $xml) use ($succeeded) {
            foreach (['2024-03-19', '2024-03-20', '2024-03-21'] as $day) {
                $xml->writeDay('representatives', $day, Xml2dbFixtures::day('representatives', $day));
            }
            return $succeeded($xml->run(['--debates', '--from=2024-03-20', '--to=2024-03-21', '--cronquiet']));
        }
],
        'a reload with changed text' => [function (Xml2dbRunner $xml) use ($succeeded, $house, $date) {
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day());
            $first = $xml->run($house);
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day('representatives', $date, '<p>Corrected words.</p>', '1'));
            return $succeeded($first, $xml->run($house));
        }
],
        'a reload where only proof changed' => [function (Xml2dbRunner $xml) use ($succeeded, $house, $date) {
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day('representatives', $date, '<p>Words.</p>', '1'));
            $first = $xml->run($house);
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day('representatives', $date, '<p>Words.</p>', '0'));
            return $succeeded($first, $xml->run($house));
        }
],
        'a missing item without --force' => [function (Xml2dbRunner $xml) use ($succeeded, $house, $date) {
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day());
            $first = $xml->run($house);
            $xml->writeDay('representatives', $date, Xml2dbFixtures::wrap(Xml2dbFixtures::majorHeading('representatives', $date, 'TREASURY')));
            return $succeeded($first, $xml->run($house));
        }
],
        'a missing item with --force' => [function (Xml2dbRunner $xml) use ($succeeded, $house, $date) {
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day());
            $first = $xml->run($house);
            $xml->writeDay('representatives', $date, Xml2dbFixtures::wrap(Xml2dbFixtures::majorHeading('representatives', $date, 'TREASURY')));
            return $succeeded($first, $xml->run([...$house, '--force']));
        }
],
        'a gid redirect' => [function (Xml2dbRunner $xml) use ($succeeded, $house, $date) {
            $xml->writeDay('representatives', $date, Xml2dbFixtures::day());
            $first = $xml->run($house);
            $old = Xml2dbFixtures::gid('representatives', $date, 1, 3);
            $new = Xml2dbFixtures::gid('representatives', $date, 1, 4);
            $items = Xml2dbFixtures::majorHeading('representatives', $date, 'TREASURY') .
                Xml2dbFixtures::minorHeading('representatives', $date, 'Fictional Bill 2024') .
                Xml2dbFixtures::speech('representatives', $date, 4, '<p>First words.</p>') .
                "<gidredirect oldgid=\"$old\" newgid=\"$new\" matchtype=\"exact\"/>\n";
            $xml->writeDay('representatives', $date, Xml2dbFixtures::wrap($items));
            return $succeeded($first, $xml->run($house));
        }
],
        'a speech before any heading' => [function (Xml2dbRunner $xml) use ($succeeded, $house, $date) {
            $xml->writeDay('representatives', $date, Xml2dbFixtures::wrap(Xml2dbFixtures::speech('representatives', $date, 3, '<p>Words.</p>')));
            return $succeeded($xml->run($house));
        }
],
        'members, then changed and removed' => [function (Xml2dbRunner $xml) use ($succeeded, $members) {
            $xml->writeMembers($members('Example Party'));
            $first = $xml->run(['--members', '--all']);
            $xml->writeMembers($members('New Party'));
            $second = $xml->run(['--members', '--all']);
            $xml->writeMembers(['representatives' => '<members/>'] + $members('New Party'));
            return $succeeded($first, $second, $xml->run(['--members', '--all']));
        }
],
    ];
}

describe('the perl and php loaders', function () {
    it('leave the same data', function (Closure $scenario) {
        $results = [];
        foreach (['perl', 'php'] as $implementation) {
            Xml2dbRunner::clearTables();
            $this->xml->implementation = $implementation;
            $succeeded = $scenario($this->xml);
            $results[$implementation] = ['succeeded' => $succeeded, 'data' => xml2dbSnapshot()];
        }
        expect($results['php'])->toEqual($results['perl']);
    })->with(xml2dbScenarios());

    it('are compared on data that was loaded', function () {
        $this->xml->writeDay('representatives', Xml2dbFixtures::DATE, Xml2dbFixtures::day());
        $this->xml->run(['--debates', '--date=' . Xml2dbFixtures::DATE, '--cronquiet']);
        expect(xml2dbSnapshot()['hansard'])->toHaveCount(3);
    });
});
