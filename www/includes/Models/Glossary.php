<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Glossary Eloquent model.
 *
 * @property int $glossary_id
 * @property string|null $title
 * @property string|null $body
 * @property string|null $contributor_name
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
        'contributor_name',
        'wikipedia',
        'created',
        'last_modified',
        'type',
        'visible',
    ];

    protected $casts = [
        'visible' => 'bool',
    ];

    /**
     * Visible terms a moderator approved, with who submitted them, by title and then the earliest submission.
     *
     * Each row has glossary_id, title, body, contributor_name, user_id, firstname and lastname.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Glossary> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Glossary>
     *   The same query, joined to the queue and the user.
     */
    public function scopeApproved(Builder $query): Builder {
        return $query->where('glossary.visible', 1)
          ->join('editqueue', 'editqueue.glossary_id', '=', 'glossary.glossary_id')
          ->join('users', 'users.user_id', '=', 'editqueue.user_id')
          ->where('editqueue.approved', 1)
          ->orderBy('glossary.title')
          ->orderBy('editqueue.submitted')
          ->orderBy('editqueue.edit_id')
          ->select('glossary.glossary_id', 'glossary.title', 'glossary.body', 'glossary.contributor_name', 'users.user_id', 'users.firstname', 'users.lastname');
    }

    /**
     * The approved terms, one for each term even when it has more than one approved queue entry.
     *
     * @param string $titleContains
     *   Keeps only terms whose title contains this text, when it is not empty.
     *
     * @return \Illuminate\Support\Collection<int, Glossary>
     *   The terms, by title.
     */
    public static function approvedTerms(string $titleContains = ''): Collection {
        $query = static::approved();
        if ($titleContains !== '') {
            $query->where('glossary.title', 'like', '%' . addcslashes($titleContains, '%_\\') . '%');
        }
        return $query->get()->unique('glossary_id')->values();
    }

}
