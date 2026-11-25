<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Enums\MemberOrder;

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
     * The party codes that stand for a presiding office, with that office and its house.
     */
    private const PRESIDING_OFFICERS = [
        'SPK' => ['Speaker', 'House of Representatives'],
        'CWM' => ['Deputy Speaker', 'House of Representatives'],
        'DCWM' => ['Deputy Speaker', 'House of Representatives'],
        'PRES' => ['President', 'Senate'],
        'DPRES' => ['Deputy President', 'Senate'],
    ];

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

    /**
     * Defines the relationship to the Memberinfo model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *   Related member info rows.
     */
    public function info(): HasMany {
        return $this->hasMany(Memberinfo::class, 'member_id', 'member_id');
    }

    /**
     * Defines the relationship to person info rows for this member's person.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *   Related person info rows.
     */
    public function personInfo(): HasMany {
        return $this->hasMany(Personinfo::class, 'person_id', 'person_id');
    }

    /**
     * The offices this member's person holds now.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *   Office rows with no end date.
     */
    public function currentOffices(): HasMany {
        return $this->hasMany(Moffice::class, 'person', 'person_id')
          ->where('to_date', '9999-12-31')
          ->orderBy('moffice_id');
    }

    /**
     * Terms in one house.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Member> $query
     *   The query to narrow.
     * @param \OpenAustralia\TWFY\Enums\House $house
     *   The house.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Member>
     *   The same query, limited to that house.
     */
    public function scopeInHouse(Builder $query, House $house): Builder {
        return $query->where('house', $house->value);
    }

    /**
     * Terms still running: those ending on the latest date in the table, which is 9999-12-31 while members sit.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Member> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Member>
     *   The same query, limited to running terms.
     */
    public function scopeSitting(Builder $query): Builder {
        return $query->where('left_house', static::query()->max('left_house'));
    }

    /**
     * The members sitting in a house, one per person, with what a list shows about them.
     *
     * Sorting by debates leaves out anyone without a count.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Member>
     *   The members, in order.
     */
    public static function sittingList(House $house, MemberOrder $order): Collection {
        $query = static::query()->inHouse($house)->sitting()->with('currentOffices');
        foreach ($order->columns() as $column) {
            $query->orderBy($column);
        }

        if ($order !== MemberOrder::Debates) {
            return $query->get()->unique('person_id')->values();
        }

        $debates = fn ($info) => $info->where('data_key', $house->debatesKey());

        return $query->whereHas('personInfo', $debates)
          ->with(['personInfo' => $debates])
          ->get()
          ->unique('person_id')
          ->sortByDesc(fn (Member $member): int => (int) $member->personInfo->first()->data_value)
          ->values();
    }

    /**
     * The name with any title, such as "Ms Jane Citizen".
     */
    protected function fullName(): Attribute {
        return Attribute::get(fn (): string => trim($this->title . ' ' . $this->first_name . ' ' . $this->last_name));
    }

    /**
     * The party, or "-" for a presiding officer, whose party code names their office instead.
     */
    protected function partyLabel(): Attribute {
        return Attribute::get(fn (): string => isset(self::PRESIDING_OFFICERS[$this->party]) ? '-' : (string) $this->party);
    }

    /**
     * The positions held now, as a list of descriptions, with any presiding office first.
     */
    protected function positions(): Attribute {
        return Attribute::get(function (): array {
            $positions = $this->currentOffices->pluck('description')->filter()->values()->all();
            if (isset(self::PRESIDING_OFFICERS[$this->party])) {
                array_unshift($positions, implode(', ', self::PRESIDING_OFFICERS[$this->party]));
            }
            return $positions;
        });
    }

    /**
     * The address of this member's profile. Titles are left out, as the profile is found by name.
     */
    protected function profileUrl(): Attribute {
        return Attribute::get(fn (): string => WEBPATH . House::from($this->house)->profilePath()
          . make_member_url($this->first_name . ' ' . $this->last_name, $this->constituency, $this->house));
    }

    /**
     * The address of this person's small photo, if there is one.
     */
    protected function photoUrl(): Attribute {
        return Attribute::get(fn (): ?string => find_rep_image($this->person_id, true)[0]);
    }

}
