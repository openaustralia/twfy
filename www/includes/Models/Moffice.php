<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the `moffice` table.
 *
 * The primary key is `moffice_id`. This is a legacy table without Eloquent's
 * managed timestamp columns.
 */
class Moffice extends Model {

    /**
     * @var string
     */
    protected $table = 'moffice';

    /**
     * @var string
     */
    protected $primaryKey = 'moffice_id';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dept',
        'position',
        'from_date',
        'to_date',
        'person',
        'source',
    ];

    /**
     * @var array<string,string>
     */
    protected $casts = [
        'moffice_id' => 'int',
        'person'     => 'int',
        'from_date'  => 'date',
        'to_date'    => 'date',
    ];

    /**
     * The office in words, such as "Minister, Treasury". A department alone means membership of it.
     */
    protected function description(): Attribute {
        return Attribute::get(function (): string {
            $description = match (true) {
                $this->position && $this->dept => "$this->position, $this->dept",
                (bool) $this->position => $this->position,
                (bool) $this->dept => "Member, $this->dept",
                default => '',
            };
            // The loader stores some punctuation as HTML entities.
            return html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        });
    }

}
