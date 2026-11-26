<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Enums\MemberOrder;
use OpenAustralia\TWFY\Parliament\MemberUrls;
use OpenAustralia\TWFY\Support\StoredText;

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
     * The end date of a term or office that is still running.
     */
    public const NO_END_DATE = '9999-12-31';

    /**
     * The party codes that stand for a presiding office, with that office and its house.
     */
    private const PRESIDING_OFFICERS = [
        'SPK' => ['Speaker', House::Representatives],
        'CWM' => ['Deputy Speaker', House::Representatives],
        'DCWM' => ['Deputy Speaker', House::Representatives],
        'PRES' => ['President', House::Senate],
        'DPRES' => ['Deputy President', House::Senate],
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
     * The offices this member's person holds now.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *   Office rows with no end date.
     */
    public function currentOffices(): HasMany {
        return $this->hasMany(Moffice::class, 'person', 'person_id')
          ->current()
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
     * Terms of people called by a name given as one string, such as "jane van example".
     *
     * With three or more words, the second may end the first name or start the last name, so this tries both.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Member> $query
     *   The query to narrow.
     * @param string $name
     *   The first and last names, separated by a space.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Member>
     *   The same query, limited to that name. A single word matches nobody.
     */
    public function scopeNamed(Builder $query, string $name): Builder {
        $words = explode(' ', $name, 3);
        if (count($words) < 2) {
            return $query->whereIn('member_id', []);
        }
        if (count($words) === 2) {
            return $query->where('first_name', $words[0])->where('last_name', $words[1]);
        }

        [$first, $middle, $last] = $words;

        return $query->where(fn (Builder $either) => $either
          ->where(fn (Builder $split) => $split->where('first_name', "$first $middle")->where('last_name', $last))
          ->orWhere(fn (Builder $split) => $split->where('first_name', $first)->where('last_name', "$middle $last")));
    }

    /**
     * Whether this term is still running.
     */
    public function isSitting(): bool {
        return $this->left_house->toDateString() === self::NO_END_DATE;
    }

    /**
     * Whether the party code names a presiding office, such as Speaker.
     */
    public function isPresidingOfficer(): bool {
        return isset(self::PRESIDING_OFFICERS[$this->party]);
    }

    /**
     * Whether the start is only known to the year. Some Senate terms are stored as 1 January.
     */
    public function enteredYearOnly(): bool {
        return $this->house === House::Senate->value && $this->entered_house->format('m-d') === '01-01';
    }

    /**
     * The term of whoever sits for an electorate or state, or else of whoever left it last, in either house or one.
     */
    public static function latestInElectorate(string $electorate, ?House $house = null): ?self {
        return static::query()
          ->where('constituency', $electorate)
          ->when($house, fn (Builder $query): Builder => $query->inHouse($house))
          ->orderByDesc('left_house')
          ->first();
    }

    /**
     * Terms with every word of a search in the first name, last name or electorate or state.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Member> $query
     *   The query to narrow.
     * @param string $search
     *   The words to look for, in any order. Blank matches everyone.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Member>
     *   The same query, limited to terms that match every word.
     */
    public function scopeMatching(Builder $query, string $search): Builder {
        foreach (preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            // A search word is not a pattern, so % and _ match themselves.
            $like = '%' . addcslashes($word, '\\%_') . '%';
            $query->where(fn (Builder $any) => $any
              ->where('first_name', 'like', $like)
              ->orWhere('last_name', 'like', $like)
              ->orWhere('constituency', 'like', $like));
        }

        return $query;
    }

    /**
     * Terms for one party.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Member> $query
     *   The query to narrow.
     * @param string $party
     *   The party as stored.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Member>
     *   The same query, limited to that party.
     */
    public function scopeOfParty(Builder $query, string $party): Builder {
        return $query->where('party', $party);
    }

    /**
     * The members sitting in a house, one per person, with what a list shows about them.
     *
     * @param \OpenAustralia\TWFY\Enums\House $house
     *   The house.
     * @param \OpenAustralia\TWFY\Enums\MemberOrder $order
     *   How to sort them.
     * @param string $search
     *   Words every member must match, if any.
     * @param string|null $party
     *   The one party to show, if any.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Member>
     *   The members, in order.
     */
    public static function sittingList(House $house, MemberOrder $order, string $search = '', ?string $party = null): Collection {
        $query = static::query()
          ->inHouse($house)
          ->sitting()
          ->matching($search)
          ->when($party !== null, fn (Builder $query): Builder => $query->ofParty($party))
          ->with('currentOffices');
        foreach ($order->columns() as $column) {
            $query->orderBy($column);
        }

        return $query->get()->unique('person_id')->values();
    }

    /**
     * The parties of the members sitting in a house, A to Z. A presiding officer's code names their office, not a party.
     *
     * @return \Illuminate\Support\Collection<int, string>
     *   Party names.
     */
    public static function sittingParties(House $house): SupportCollection {
        return static::query()
          ->inHouse($house)
          ->sitting()
          ->whereNotIn('party', array_keys(self::PRESIDING_OFFICERS))
          ->distinct()
          ->orderBy('party')
          ->pluck('party');
    }

    /**
     * The name with any title, such as "Ms Jane Citizen", ready to show.
     */
    protected function fullName(): Attribute {
        return Attribute::get(fn (): string => StoredText::decode(trim($this->title . ' ' . $this->first_name . ' ' . $this->last_name)));
    }

    /**
     * The first and last names without a title, ready to show.
     */
    protected function shortName(): Attribute {
        return Attribute::get(fn (): string => "$this->display_first_name $this->display_last_name");
    }

    // The columns keep their stored form, which lookups and addresses use. These are for showing them.

    /**
     * The first name, ready to show.
     */
    protected function displayFirstName(): Attribute {
        return Attribute::get(fn (): string => StoredText::decode($this->first_name));
    }

    /**
     * The last name, ready to show.
     */
    protected function displayLastName(): Attribute {
        return Attribute::get(fn (): string => StoredText::decode($this->last_name));
    }

    /**
     * The electorate or state, ready to show.
     */
    protected function displayConstituency(): Attribute {
        return Attribute::get(fn (): string => StoredText::decode($this->constituency));
    }

    /**
     * The party, or "-" for a presiding officer, whose party code names their office instead.
     */
    protected function partyLabel(): Attribute {
        return Attribute::get(fn (): string => $this->isPresidingOfficer() ? '-' : (string) $this->party);
    }

    /**
     * The party, or the office a presiding officer's party code stands for.
     */
    protected function partyName(): Attribute {
        return Attribute::get(fn (): string => self::PRESIDING_OFFICERS[$this->party][0] ?? (string) $this->party);
    }

    /**
     * The positions held now, as a list of descriptions, with any presiding office first.
     */
    protected function positions(): Attribute {
        return Attribute::get(function (): array {
            $positions = $this->currentOffices->pluck('description')->filter()->values()->all();
            if ($this->isPresidingOfficer()) {
                [$office, $house] = self::PRESIDING_OFFICERS[$this->party];
                array_unshift($positions, "$office, {$house->title()}");
            }
            return $positions;
        });
    }

    /**
     * The address of this member's profile. Titles are left out, as the profile is found by name.
     */
    protected function profileUrl(): Attribute {
        return Attribute::get(fn (): string => MemberUrls::profile(
            House::from($this->house),
            "$this->first_name $this->last_name",
            $this->constituency,
        ));
    }

    /**
     * The address of this person's small photo, if there is one.
     */
    protected function photoUrl(): Attribute {
        return Attribute::get(fn (): ?string => find_rep_image($this->person_id, true)[0]);
    }

}
