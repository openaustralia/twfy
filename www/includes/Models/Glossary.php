<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Glossary Eloquent model.
 *
 * @property int $glossary_id
 * @property string|null $title
 * @property string|null $body
 * @property string|null $wikipedia
 * @property string|null $created
 * @property string|null $last_modified
 * @property int|null $type
 * @property int|null $visible
 */
class Glossary extends Model {

    protected $table = 'glossary';

    protected $primaryKey = 'glossary_id';

    protected $fillable = [
        'title',
        'body',
        'wikipedia',
        'created',
        'last_modified',
        'type',
        'visible',
    ];

    protected $casts = [
        'visible' => 'bool',
    ];

}
