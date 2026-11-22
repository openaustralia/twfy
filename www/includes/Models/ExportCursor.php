<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The highest id an exporter has sent, by exporter name.
 *
 * @property string $name
 * @property int $last_id
 */
class ExportCursor extends Model {

    protected $table = 'export_cursors';

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The database fills created_at and updated_at. Eloquent would use PHP's
     * clock, which the site sets to Sydney time while MySQL runs in UTC.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'name',
        'last_id',
    ];

    protected $casts = [
        'last_id' => 'integer',
    ];

    /**
     * The last id sent by the named exporter, or 0 if it has never run.
     */
    public static function lastId(string $name): int {
        return (int) (static::whereKey($name)->value('last_id') ?? 0);
    }

    /**
     * Records the last id sent by the named exporter.
     */
    public static function advance(string $name, int $lastId): void {
        static::upsert([['name' => $name, 'last_id' => $lastId]], ['name'], ['last_id']);
    }

}
