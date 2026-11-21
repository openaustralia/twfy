<?php

namespace OpenAustralia\TWFY\XmlImport;

use Illuminate\Database\Capsule\Manager as Capsule;
use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Gidredirect;
use OpenAustralia\TWFY\Models\Hansard;

/**
 * Loads one house's debates, a day at a time: the PHP version of add_debates_day in xml2db.pl.
 *
 * Each day is read twice. The first pass only collects the gids, so that rows missing from the XML can be
 * found before anything changes. The second pass writes the rows, matching them by gid.
 */
class DebatesLoader {

    /**
     * The gids seen in this pass, and where gid redirects point.
     *
     * @var array<string, bool>
     */
    private array $gids = [];

    /**
     * @var array<string, string>
     */
    private array $redirects = [];

    /**
     * The gids this pass has already handled, which later copies of the same gid skip.
     *
     * @var array<string, bool>
     */
    private array $handled = [];

    private bool $tally = false;

    private int $dummyId = 10;

    private int $hpos = 0;

    private int $section = 0;

    private int $subsection = 0;

    private int $promoted = 0;

    private string $date = '';

    private ?string $proof = null;

    /**
     * Creates a loader.
     *
     * @param \OpenAustralia\TWFY\XmlImport\Options $options
     *   The command-line options.
     * @param \OpenAustralia\TWFY\XmlImport\Messages $messages
     *   Where progress is printed.
     * @param string $directory
     *   The directory of this house's day files, with a trailing slash.
     * @param int $major
     *   The hansard.major value for this house: 1 for the House, 101 for the Senate.
     */
    public function __construct(
      private readonly Options $options,
      private readonly Messages $messages,
      private readonly string $directory,
      private readonly int $major,
    ) {
    }

    /**
     * The dates that have a file, and fall in the requested range, oldest first.
     *
     * @return list<string>
     *   Dates as YYYY-MM-DD.
     */
    public function datesToLoad(): array {
        [$from, $to] = $this->options->dateRange();
        $dates = [];
        foreach (scandir($this->directory) ?: [] as $file) {
            if (preg_match('/^(\d{4}-\d\d-\d\d)([a-z]*)\.xml$/', $file, $match)
              || preg_match('/^\d{4}-\d\d-\d\d_[^_]*_[^_]*_(\d{4}-\d\d-\d\d)([a-z]*)\.xml$/', $file, $match)) {
                if ($this->options->all || ($from <= $match[1] && $match[1] <= $to)) {
                    $dates[$match[1]] = true;
                }
            }
        }
        $dates = array_keys($dates);
        sort($dates, SORT_STRING);
        return $dates;
    }

    /**
     * Loads one day.
     */
    public function loadDay(string $date): void {
        $this->date = $date;
        $files = glob($this->directory . $date . '*.xml') ?: [];

        $this->startPass(true);
        $this->redirects = [];
        $this->parseFiles($files);
        $this->checkExtraGids(array_keys($this->gids));

        $this->startPass(false);
        $this->parseFiles($files);
        $this->deleteRedirectedGids();
    }

    /**
     * Resets the counters at the start of a pass.
     */
    private function startPass(bool $tally): void {
        $this->hpos = 0;
        $this->section = 0;
        $this->subsection = 0;
        $this->promoted = 0;
        $this->tally = $tally;
        $this->gids = [];
        $this->handled = [];
        $this->dummyId = 10;
    }

    /**
     * Handles every element of every file for the day, in document order.
     *
     * @param list<string> $files
     *   Paths of the day's files.
     */
    private function parseFiles(array $files): void {
        foreach ($files as $file) {
            $this->proof = null;
            $document = new \DOMDocument();
            if (!@$document->load($file, LIBXML_NONET)) {
                throw new \RuntimeException("Could not parse $file");
            }
            $selfClosing = XmlText::selfClosingFlags($file);
            foreach ($document->getElementsByTagName('*') as $index => $element) {
                match ($element->nodeName) {
                    'debates' => $this->proof = $element->hasAttribute('proof') ? $element->getAttribute('proof') : null,
                    'speech' => $this->speech($element, XmlText::innerXml($element, $selfClosing, $index)),
                    'minor-heading' => $this->subheading($element, XmlText::strip(XmlText::innerXml($element, $selfClosing, $index))),
                    'major-heading' => $this->heading($element, XmlText::strip(XmlText::innerXml($element, $selfClosing, $index))),
                    'gidredirect' => $this->gidRedirect($element),
                    default => null,
                };
            }
        }
    }

    /**
     * An attribute's value, or NULL when the element does not have it.
     */
    private function attribute(\DOMElement $element, string $name): ?string {
        return $element->hasAttribute($name) ? $element->getAttribute($name) : null;
    }

    /**
     * A speech: with a speaker (type 12) or procedural, with none (type 13).
     */
    private function speech(\DOMElement $speech, string $text): void {
        if ($text === '') {
            return;
        }
        $id = $this->attribute($speech, 'id');
        if (isset($this->handled[$id])) {
            return;
        }
        if ($this->section === 0 && $this->subsection === 0) {
            throw new \RuntimeException("speech without (sub)heading $id '$text'");
        }

        $this->hpos++;
        $time = $this->attribute($speech, 'time');
        $time = $time === null ? null : XmlText::canonTime($time);

        $speakerId = $this->attribute($speech, 'speakerid');
        $prefix = '';
        if ($speakerId !== null && $speakerId !== '' && $speakerId !== '0') {
            $type = 12;
            $speaker = preg_replace('#^uk\.org\.publicwhip/(member|lord|royal)/#', '', $speakerId);
            if ($speaker === 'unknown') {
                $speaker = '0';
                $prefix = '<p class="unknownspeaker">' . $this->attribute($speech, 'speakername') . ':</p> ';
            }
        } else {
            $type = 13;
            $speaker = '0';
        }

        $this->addPair($prefix . $text, [
            $id, $type, $speaker, $this->major, 0, $this->section, $this->subsection, $this->hpos,
            $this->date, $time, $this->attribute($speech, 'url'), $this->proof,
        ]);
    }

    /**
     * A major heading: a section of its own.
     */
    private function heading(\DOMElement $element, string $text, int $minor = 0): void {
        $id = $this->attribute($element, 'id');
        if (isset($this->handled[$id])) {
            return;
        }
        $this->hpos++;
        $time = $this->attribute($element, 'time');
        $time = $time === null ? null : XmlText::canonTime($time);

        $epobjectId = $this->addPair(XmlText::fixCase($text), [
            $id, 10, '0', $this->major, $minor, 0, 0, $this->hpos,
            $this->date, $time, $this->attribute($element, 'url'), $this->proof,
        ]);
        $this->subsection = $epobjectId;
        $this->section = $epobjectId;
    }

    /**
     * A minor heading: under the current section, or a section of its own when there is none.
     */
    private function subheading(\DOMElement $element, string $text, int $minor = 0): void {
        $id = $this->attribute($element, 'id');
        if (isset($this->handled[$id])) {
            return;
        }
        if ($this->promoted === $this->section) {
            $this->section = 0;
        }
        if ($this->section === 0) {
            $this->heading($element, $text, $minor);
            $this->promoted = $this->section;
            return;
        }

        $this->hpos++;
        $time = $this->attribute($element, 'time');
        $time = $time === null ? null : XmlText::canonTime($time);

        $this->subsection = $this->addPair(XmlText::fixCase($text), [
            $id, 11, '0', $this->major, $minor, $this->section, 0, $this->hpos,
            $this->date, $time, $this->attribute($element, 'url'), $this->proof,
        ]);
    }

    /**
     * A gid redirect: the old gid's comments and votes move to the new gid, and the old row goes.
     */
    private function gidRedirect(\DOMElement $element): void {
        $old = (string) $this->attribute($element, 'oldgid');
        $new = (string) $this->attribute($element, 'newgid');
        $matchType = (string) $this->attribute($element, 'matchtype');

        $this->handled[$old] = true;

        if ($this->tally) {
            if ($matchType !== 'multiplecover' && isset($this->gids[$old]) && $this->redirects[$old] !== $new) {
                throw new \RuntimeException("Got gid $old twice, with different destinations, in XML file");
            }
            $this->gids[$old] = true;
            $this->redirects[$old] = $new;
            return;
        }
        if ($matchType === 'multiplecover' && isset($this->gids[$old])) {
            return;
        }
        $this->gids[$old] = true;
        if ($matchType === 'removed') {
            return;
        }
        Gidredirect::query()->toBase()->updateOrInsert(
          ['gid_from' => $old],
          ['gid_to' => $new, 'hdate' => $this->date, 'major' => $this->major]
        );
    }

    /**
     * Saves one item and its text, returning the epobject id.
     *
     * In the first pass it only records the gid, and returns a made-up id.
     *
     * @param string $body
     *   The text.
     * @param list<string|int|null> $row
     *   The hansard values after epobject_id: gid, htype, speaker_id, major, minor, section_id, subsection_id,
     *   hpos, hdate, htime, source_url, proof. The proof flag has to stay last.
     */
    private function addPair(string $body, array $row): int {
        $gid = $row[0];
        $this->handled[$gid] = true;
        if ($row[9] === '') {
            $row[9] = '00:00:00';
        }

        if ($this->tally) {
            if (isset($this->gids[$gid])) {
                throw new \RuntimeException("Got gid $gid twice in XML file");
            }
            $this->gids[$gid] = true;
            return ++$this->dummyId;
        }

        Gidredirect::query()->toBase()->where('gid_from', $gid)->delete();

        $existing = Hansard::query()->toBase()->where('gid', $gid)->get();
        if ($existing->count() > 1) {
            throw new \RuntimeException("More than one existing hansard object of same gid $gid");
        }

        if ($existing->count() === 1) {
            $current = $existing->first();
            $epobjectId = (int) $current->epobject_id;
            $text = Epobject::query()->toBase()->where('epobject_id', $epobjectId)->first();
            if ($text === null) {
                $this->messages->say("strange, missing epobject $epobjectId for gid $gid - part of db unexpectedly missing\n");
                $this->messages->say("deleting hansard object $gid and rebuilding\n");
                Hansard::query()->toBase()->where('gid', $gid)->where('epobject_id', $epobjectId)->delete();
            } else {
                $this->update($current, $text->body, $body, $row);
                return $epobjectId;
            }
        }

        $now = Capsule::raw('NOW()');
        $epobjectId = (int) Epobject::query()->toBase()->insertGetId([
            'title' => '',
            'body' => $body,
            'type' => 1,
            'created' => $now,
            'modified' => $now,
        ]);
        Hansard::query()->toBase()->insert([
            'epobject_id' => $epobjectId,
            'gid' => $row[0],
            'htype' => $row[1],
            'speaker_id' => $row[2],
            'major' => $row[3],
            'minor' => $row[4],
            'section_id' => $row[5],
            'subsection_id' => $row[6],
            'hpos' => $row[7],
            'hdate' => $row[8],
            'htime' => $row[9],
            'source_url' => $row[10],
            'proof' => $row[11],
            'created' => $now,
            'modified' => $now,
        ]);
        return $epobjectId;
    }

    /**
     * Brings an existing item and its text up to date, changing only what differs.
     *
     * @param object $current
     *   The stored hansard row.
     * @param string|null $storedBody
     *   The stored text.
     * @param string $body
     *   The text from the XML.
     * @param list<string|int|null> $row
     *   The new hansard values, as for addPair().
     */
    private function update(object $current, ?string $storedBody, string $body, array $row): void {
        $stored = [
            $current->gid, $current->htype, $current->speaker_id, $current->major, $current->minor,
            $current->section_id, $current->subsection_id, $current->hpos, $current->hdate, $current->htime,
            $current->source_url, $current->proof,
        ];
        $epobjectId = (int) $current->epobject_id;
        $gid = $row[0];
        $last = count($row) - 1;

        $rowNoProof = array_slice($row, 0, $last);
        $storedNoProof = array_slice($stored, 0, $last);
        if ($this->same($storedNoProof, $rowNoProof)) {
            // Only proof can differ. Leave modified alone, so the search index and alerts see no change.
            $storedProof = [$stored[$last]];
            $newProof = [$row[$last]];
            if (!$this->same($storedProof, $newProof)) {
                Hansard::query()->toBase()->where('epobject_id', $epobjectId)->update(['proof' => $row[$last]]);
            }
        } elseif (!$this->same($stored, $row)) {
            if (!$this->options->quiet) {
                $this->messages->say("updating hansard object $gid, changing: " . $this->describe($stored, $row) . "\n");
            }
            Hansard::query()->toBase()->where('epobject_id', $epobjectId)->where('gid', $gid)->update([
                'gid' => $row[0],
                'htype' => $row[1],
                'speaker_id' => $row[2],
                'major' => $row[3],
                'minor' => $row[4],
                'section_id' => $row[5],
                'subsection_id' => $row[6],
                'hpos' => $row[7],
                'hdate' => $row[8],
                'htime' => $row[9],
                'source_url' => $row[10],
                'proof' => $row[11],
                'modified' => Capsule::raw('NOW()'),
            ]);
        }

        $storedText = [$storedBody];
        $newBody = [$body];
        if (!$this->same($storedText, $newBody)) {
            if (!$this->options->quiet) {
                $this->messages->say("updating epobject epid $epobjectId for gid $gid, changing: " . $this->describe([$storedBody], [$body]) . "\n");
            }
            Epobject::query()->toBase()->where('epobject_id', $epobjectId)->update(['body' => $body, 'modified' => Capsule::raw('NOW()')]);
        }
    }

    /**
     * True when the two lists hold the same values, comparing as strings and NULL only to NULL.
     *
     * A time of "unknown" in the second list counts as 00:00:00, as in xml2db.pl's compare_arrays().
     *
     * @param list<string|int|null> $first
     *   The stored values.
     * @param list<string|int|null> $second
     *   The new values. An "unknown" in it is changed to 00:00:00.
     */
    private function same(array $first, array &$second): bool {
        if (count($first) !== count($second)) {
            return false;
        }
        foreach ($first as $i => $value) {
            if ($value === null && $second[$i] === null) {
                continue;
            }
            if ($value === null || $second[$i] === null) {
                return false;
            }
            if ((string) $second[$i] === 'unknown') {
                $second[$i] = '00:00:00';
            }
            if ((string) $value !== (string) $second[$i]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Says what differs between two lists, for the "updating" messages.
     *
     * @param list<string|int|null> $first
     *   The stored values.
     * @param list<string|int|null> $second
     *   The new values.
     */
    private function describe(array $first, array $second): string {
        $text = '';
        foreach ($first as $i => $from) {
            $to = $second[$i];
            if ($from !== null && $to === null) {
                $text .= "at $i value #$from# to <undef>. ";
            } elseif ($from === null && $to !== null) {
                $text .= "at $i value <undef> to #$to#. ";
            } elseif ($from !== null && (string) $from !== (string) $to) {
                $text .= "at $i value #$from# to #$to#. ";
            }
        }
        return $text;
    }

    /**
     * Finds rows in the database for this day and house that the XML no longer has.
     *
     * Without --force it reports them and stops. With --force it deletes them, unless a comment, vote or edit
     * points at one and no redirect covers it.
     *
     * @param list<string> $xmlGids
     *   The gids the first pass saw, including the old side of redirects.
     */
    private function checkExtraGids(array $xmlGids): void {
        $inDatabase = Hansard::query()->toBase()->where('hdate', $this->date)->where('gid', 'not like', '%L')
          ->where('major', $this->major)->pluck('gid')->all();
        $redirected = Gidredirect::query()->toBase()->where('hdate', $this->date)
          ->where('major', $this->major)->pluck('gid_from')->all();
        $all = array_merge($inDatabase, $redirected);
        sort($all, SORT_STRING);
        $inXml = array_flip($xmlGids);

        $missing = 0;
        foreach ($all as $gid) {
            if (isset($inXml[$gid])) {
                continue;
            }
            $missing++;
            $vital = 0;
            foreach (self::REFERENCES as [$table, $field]) {
                $count = Capsule::table('epobject')
                  ->join('hansard', 'epobject.epobject_id', '=', 'hansard.epobject_id')
                  ->join($table, "epobject.epobject_id", '=', "$table.$field")
                  ->where('hansard.gid', $gid)->count();
                if ($count > 0) {
                    $this->messages->say("VITAL ERROR! gid $gid needs deleting, has an entry in table $table, but no gid redirect\n");
                    $vital++;
                }
            }
            if ($this->options->force) {
                if ($vital > 0) {
                    throw new \RuntimeException("Refusing to even force delete, when there are references in other tables\n");
                }
                Hansard::query()->toBase()->where('gid', $gid)->delete();
                Gidredirect::query()->toBase()->where('gid_from', $gid)->delete();
                $this->messages->say("FORCED deleting $gid from db, wasn't in XML\n");
            } else {
                $this->messages->say("gid $gid in database not in XML, run again with --force to delete\n");
            }
        }
        if ($missing > 0) {
            if (!$this->options->force) {
                throw new \RuntimeException('Died');
            }
            Importer::deleteLonelyEpobjects($this->messages);
        }
    }

    /**
     * The tables and columns that point at an epobject, and so stop a forced delete.
     */
    private const REFERENCES = [
        ['comments', 'epobject_id'],
        ['anonvotes', 'epobject_id'],
        ['editqueue', 'epobject_id_l'],
        ['editqueue', 'epobject_id_h'],
    ];

    /**
     * Moves comments and votes from each redirected gid to where it now points, then deletes the old row.
     */
    private function deleteRedirectedGids(): void {
        ksort($this->redirects, SORT_STRING);
        foreach ($this->redirects as $from => $to) {
            while (($next = Gidredirect::query()->toBase()->where('gid_from', $to)->value('gid_to')) !== null) {
                $to = $next;
            }
            $newId = Hansard::query()->toBase()->where('gid', $to)->value('epobject_id');
            if (!$newId) {
                continue;
            }
            foreach (self::REFERENCES as [$table, $field]) {
                $count = Capsule::table('epobject')
                  ->join('hansard', 'epobject.epobject_id', '=', 'hansard.epobject_id')
                  ->join($table, "epobject.epobject_id", '=', "$table.$field")
                  ->where('hansard.gid', $from)->count();
                if ($count > 0) {
                    $oldId = Hansard::query()->toBase()->where('gid', $from)->value('epobject_id');
                    $this->messages->info("gid $from has $count " . ($count === 1 ? 'entry' : 'entries') . " in table $table, new gid $to\n");
                    $this->moveReferences($table, $field, (int) $oldId, (int) $newId);
                }
            }
            if (Hansard::query()->toBase()->where('gid', $from)->delete() > 0) {
                $this->messages->info("deleted $from which is now redirected to $to\n");
            }
        }
    }

    /**
     * Points rows in $table at a new epobject. Anonymous votes on both are added together.
     */
    private function moveReferences(string $table, string $field, int $oldId, int $newId): void {
        $this->messages->info("updating epobject id from $oldId => $newId\n");
        if ($table === 'anonvotes') {
            $already = Capsule::table('anonvotes')->where('epobject_id', $newId)->first();
            if ($already) {
                Capsule::table('anonvotes')->where('epobject_id', $newId)->delete();
                Capsule::table('anonvotes')->where('epobject_id', $oldId)->update([
                    'yes_votes' => Capsule::raw('yes_votes + ' . (int) $already->yes_votes),
                    'no_votes' => Capsule::raw('no_votes + ' . (int) $already->no_votes),
                    'epobject_id' => $newId,
                ]);
                return;
            }
        }
        Capsule::table($table)->where($field, $oldId)->update([$field => $newId]);
    }

}
