<?php

namespace OpenAustralia\TWFY\XmlImport;

use Illuminate\Database\Capsule\Manager as Capsule;
use OpenAustralia\TWFY\Models\Constituency;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Moffice;

/**
 * Loads members, their offices and the person each belongs to: the PHP version of add_mps_and_peers in xml2db.pl.
 *
 * Constituencies are cleared but not loaded. xml2db.pl has a handler for them that never fires, because
 * divisions.xml holds division elements, not constituency elements, and the constituency table stays empty.
 */
class MembersLoader {

    /**
     * The person each member or office id belongs to, from people.xml.
     *
     * @var array<string, string>
     */
    private array $personFor = [];

    /**
     * Members seen in the files, by member id.
     *
     * @var array<int|string, bool>
     */
    private array $seen = [];

    /**
     * Offices waiting to be joined and saved: id, department, position, from, to, person, source.
     *
     * @var list<array<int, string|null>>
     */
    private array $offices = [];

    /**
     * Creates a loader.
     *
     * @param \OpenAustralia\TWFY\XmlImport\Messages $messages
     *   Where progress is printed.
     * @param string $directory
     *   The members directory, with a trailing slash.
     */
    public function __construct(private readonly Messages $messages, private readonly string $directory) {
    }

    /**
     * Replaces the offices and constituencies, and adds or updates the members.
     */
    public function load(): void {
        Moffice::query()->toBase()->delete();
        Constituency::query()->toBase()->delete();

        foreach (['divisions', 'people', 'representatives', 'senators', 'ministers'] as $file) {
            $this->parse($this->directory . $file . '.xml');
        }
        $this->saveOffices();
        $this->reportMissingMembers();
    }

    /**
     * Handles the elements of one file in document order.
     */
    private function parse(string $path): void {
        $document = new \DOMDocument();
        if (!@$document->load($path, LIBXML_NONET)) {
            throw new \RuntimeException("Could not parse $path");
        }
        foreach ($document->getElementsByTagName('*') as $element) {
            match ($element->nodeName) {
                'person' => $this->person($element),
                'member' => $this->member($element),
                'moffice' => $this->office($element),
                default => null,
            };
        }
    }

    /**
     * Records which person each of this person's offices belongs to.
     */
    private function person(\DOMElement $person): void {
        foreach ($person->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->nodeName === 'office') {
                $this->personFor[$child->getAttribute('id')] = $person->getAttribute('id');
            }
        }
    }

    /**
     * A member of the House of Representatives or the Senate.
     */
    private function member(\DOMElement $member): void {
        $id = $member->getAttribute('id');
        $house = $member->getAttribute('house');
        if ($house === 'representatives') {
            $id = str_replace('uk.org.publicwhip/member/', '', $id);
            $houseNumber = 1;
        } elseif ($house === 'senate') {
            $id = str_replace('uk.org.publicwhip/lord/', '', $id);
            $houseNumber = 2;
        } else {
            throw new \RuntimeException('Unknown house');
        }
        if (!isset($this->personFor[$member->getAttribute('id')])) {
            throw new \RuntimeException("Member $id has no person");
        }
        $personId = str_replace('uk.org.publicwhip/person/', '', $this->personFor[$member->getAttribute('id')]);

        // Names are saved as HTML entities, as e.g. &Ouml;, because non-ASCII characters got lost between
        // Perl, the database and the browser. Constituencies keep their & signs for the postcode search.
        $this->saveMember([
            'member_id' => $id,
            'person_id' => $personId,
            'house' => $houseNumber,
            'title' => XmlText::entitiesNoApos($member->getAttribute('title')),
            'first_name' => XmlText::entitiesNoApos($member->getAttribute('firstname')),
            'last_name' => XmlText::entitiesNoApos($member->getAttribute('lastname')),
            'constituency' => XmlText::entitiesNoApos($member->getAttribute('division')),
            'party' => $member->getAttribute('party'),
            'entered_house' => $member->getAttribute('fromdate'),
            'left_house' => $member->getAttribute('todate'),
            'entered_reason' => $member->getAttribute('fromwhy'),
            'left_reason' => $member->getAttribute('towhy'),
        ]);
    }

    /**
     * Adds a member, or replaces one whose details changed.
     *
     * @param array<string, string|int> $values
     *   The member columns.
     */
    private function saveMember(array $values): void {
        $id = $values['member_id'];
        $exists = Member::query()->toBase()->where('member_id', $id)->exists();
        if ($exists && Member::query()->toBase()->where($values)->exists()) {
            $this->seen[$id] = true;
            return;
        }
        if ($exists) {
            $this->messages->say("Replacing existing member with new data for $id\n");
        } else {
            $this->messages->say("Adding new member with identifier $id\n");
        }
        $this->messages->say("This is for your information only, just check it looks OK.\n\n");

        // REPLACE INTO, because Eloquent has no replace: it also deletes a row that clashes on the
        // unique name-and-dates key, which an upsert would not.
        $columns = array_keys($values);
        Capsule::connection()->statement(
          'REPLACE INTO member (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
          array_values($values)
        );
        $this->seen[$id] = true;
    }

    /**
     * A ministerial office, kept until all of them can be joined.
     */
    private function office(\DOMElement $office): void {
        $memberId = $office->getAttribute('matchid');
        if ($memberId === '') {
            return;
        }
        if (!isset($this->personFor[$memberId])) {
            throw new \RuntimeException("mp $memberId " . $office->getAttribute('name') . ' has no person');
        }
        $person = str_replace('uk.org.publicwhip/person/', '', $this->personFor[$memberId]);

        $position = $office->getAttribute('position');
        if ($office->getAttribute('responsibility') !== '' && $office->getAttribute('responsibility') !== '0') {
            $position .= ' (' . $office->getAttribute('responsibility') . ')';
        }

        $this->offices[] = [
            str_replace('uk.org.publicwhip/moffice/', '', $office->getAttribute('id')),
            XmlText::entitiesNoApos($office->getAttribute('dept')),
            XmlText::entitiesNoApos($position),
            $office->getAttribute('fromdate'),
            $office->getAttribute('todate'),
            $person,
            $office->getAttribute('source'),
        ];
    }

    /**
     * Joins offices that follow on from each other, then saves them.
     *
     * Offices are not consecutive in the XML, so every earlier office is checked. One that starts on the day
     * another ends, for the same person, department and position, extends the earlier one.
     */
    private function saveOffices(): void {
        $offices = $this->offices;
        usort($offices, static fn (array $a, array $b): int => strcmp((string) $a[3], (string) $b[3]));
        $offices = array_values($offices);

        $total = count($offices);
        for ($i = 0; $i < $total; $i++) {
            for ($j = 0; $j < $i; $j++) {
                if (!isset($offices[$j])) {
                    continue;
                }
                if ($offices[$i][5] === $offices[$j][5] && $offices[$i][1] === $offices[$j][1]
                  && $offices[$i][2] === $offices[$j][2] && $offices[$i][3] === $offices[$j][4]) {
                    $offices[$j][4] = $offices[$i][4];
                    unset($offices[$i]);
                    break;
                }
            }
        }
        foreach ($offices as $office) {
            Moffice::query()->toBase()->insert([
                'dept' => $office[1],
'position' => $office[2],
'from_date' => $office[3],
                'to_date' => $office[4],
'person' => $office[5],
'source' => $office[6],
            ]);
        }
    }

    /**
     * Reports members in the database that the files did not have.
     */
    private function reportMissingMembers(): void {
        foreach (Member::query()->toBase()->pluck('member_id') as $id) {
            if (!isset($this->seen[$id])) {
                $this->messages->say("Member $id in DB, not in XML\n");
            }
        }
    }

}
