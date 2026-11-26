<?php

namespace OpenAustralia\TWFY\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use OpenAustralia\TWFY\Enums\MemberOrder;

/**
 * What a list of members can be asked: a sort order, a search and a party.
 */
class MemberListRequest extends FormRequest {

    /**
     * @return array<string, list<string>>
     *   Rules for each query parameter.
     */
    public function rules(): array {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'party' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Sends a bad request to the plain list, as these pages start no session to flash errors into.
     */
    protected function failedValidation(Validator $validator): void {
        throw new HttpResponseException(redirect($this->url()));
    }

    /**
     * The order asked for, or last name.
     */
    public function order(): MemberOrder {
        return MemberOrder::fromQuery($this->query('o'));
    }

    /**
     * The words to look for in names and electorates or states, or an empty string.
     */
    public function search(): string {
        return trim((string) $this->validated('q'));
    }

    /**
     * The party to show, if one is asked for.
     */
    public function party(): ?string {
        return $this->validated('party') ?: null;
    }

    /**
     * The search and party, for links that keep the filters.
     *
     * @return array<string, string>
     *   Query parameters that are set.
     */
    public function filters(): array {
        return array_filter(['q' => $this->search(), 'party' => $this->party()]);
    }

    /**
     * Whether the list is wanted as a spreadsheet.
     */
    public function wantsCsv(): bool {
        return $this->query('f') === 'csv';
    }

}
