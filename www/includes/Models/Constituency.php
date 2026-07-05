<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Constituency Eloquent model — READ-ONLY.
 *
 * Maps to the constituency table containing parliamentary constituency information.
 *
 * IMPORTANT: This table has NO PRIMARY KEY defined in the schema.
 *
 * Data flows one direction: openaustralia-parser → scripts/xml2db.pl → database
 * The PHP codebase is READ-ONLY for this table in production.
 *
 * Data pipeline:
 * 1. openaustralia-parser (Ruby, ../openaustralia-parser/)
 *    - Parses Hansard transcripts and generates structured member/division data
 *    - Writes constituency data to XML output
 *    - See: openaustralia-parser/lib/parser/ and openaustralia-parser/lib/xml_generators/
 * 2. scripts/xml2db.pl (Perl)
 *    - Reads the XML output from openaustralia-parser
 *    - Deletes all existing constituencies
 *    - Re-inserts them from XML data during updates
 *    - Runs via scripts/update-hansard.pl
 *
 * NOTE: Tests may create fixture data using this model for testing purposes.
 *
 * @property string $name
 * @property bool $main_name
 * @property string $from_date
 * @property string $to_date
 * @property int|null $cons_id
 */
class Constituency extends Model {

    protected $table = 'constituency';

    public $timestamps = true;

    // Prevent mass assignment to protect read-only status.
    protected $guarded = ['*'];

    // Cast date fields.
    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'main_name' => 'bool',
    ];

}
