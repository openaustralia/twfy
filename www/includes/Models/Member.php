<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent model for the `member` table.
 *
 * Notes on the legacy schema:
 *   - The table name is singular (`member`), not Eloquent's pluralised default.
 *   - `member_id` is the primary key but is NOT auto-increment; values are
 *     assigned externally by the import scripts.
 *   - `created_at` / `updated_at` are managed by Eloquent. The legacy
 *     `lastupdate` column (MySQL `ON UPDATE CURRENT_TIMESTAMP`) is left in
 *     place for compatibility with existing code paths.
 */
class Member extends Model {

    /**
     * @var string
     */
    protected $table = 'member';

    /**
     * @var string
     */
    protected $primaryKey = 'member_id';

    /**
     * `member_id` is assigned by the loader, not by AUTO_INCREMENT.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $keyType = 'int';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'member_id',
        'house',
        'first_name',
        'last_name',
        'constituency',
        'party',
        'entered_house',
        'left_house',
        'entered_reason',
        'left_reason',
        'person_id',
        'title',
    ];

    /**
     * @var array<string,string>
     */
    protected $casts = [
        'house'         => 'int',
        'person_id'     => 'int',
        'entered_house' => 'date',
        'left_house'    => 'date',
        'lastupdate'    => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function info(): HasMany {
        return $this->hasMany(Memberinfo::class, 'member_id', 'member_id');
    }

}
