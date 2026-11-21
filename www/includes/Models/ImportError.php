<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One error from importing a day of Hansard.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $hdate
 * @property int $major 1 for the Representatives, 101 for the Senate, as hansard.major.
 * @property string $source "parser" or "loader".
 * @property string $message
 * @property string|null $created_at
 */
class ImportError extends Model {

    protected $table = 'import_errors';

    /**
     * The database fills created_at. Eloquent would use PHP's clock, which the site sets to Sydney time while MySQL
     * runs in UTC.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'hdate',
        'major',
        'source',
        'message',
    ];

    protected $casts = [
        'hdate' => 'date',
        'major' => 'integer',
    ];

}
