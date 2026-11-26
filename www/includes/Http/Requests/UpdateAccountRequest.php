<?php

namespace OpenAustralia\TWFY\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The form for editing your own details.
 *
 * Leaving the password blank keeps the current one, and no current password is asked for.
 */
class UpdateAccountRequest extends FormRequest {

    /**
     * Where a form with mistakes goes back to, whatever the browser sent as its referrer.
     */
    protected $redirectRoute = 'account.edit';

    /**
     * Anyone signed in may edit their own details.
     */
    public function authorize(): bool {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     *   The rules for each field.
     */
    public function rules(): array {
        return [
            // People don't all have a first and a last name, so either one is enough.
            'first_name' => ['required_without:last_name', 'nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->getKey(), 'user_id')],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'news' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     *   What each failure says, in plain words.
     */
    public function messages(): array {
        return [
            'first_name.required_without' => 'Enter a first name or a last name',
            'email.required' => 'Enter your email address',
            'email.email' => 'Enter an email address like name@example.com',
            'email.unique' => 'Someone else has already joined with this email address',
            'password.min' => 'Your new password needs at least :min characters',
            'password.confirmed' => "The two passwords don't match",
            'news.required' => 'Choose whether we can email you our news',
            'news.boolean' => 'Choose whether we can email you our news',
        ];
    }

    /**
     * @return array<string, string>
     *   Each field's name, for the messages Laravel words itself.
     */
    public function attributes(): array {
        return ['first_name' => 'first name', 'last_name' => 'last name', 'email' => 'email address'];
    }

}
