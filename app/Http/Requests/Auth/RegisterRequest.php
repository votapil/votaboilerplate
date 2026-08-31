<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\UserSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    /**
     * Normalize before validating, not after. `unique:users` compares the value
     * verbatim, so "Bob@Example.com" would slip past a uniqueness check against
     * the stored "bob@example.com" and only fail on the database index — as a
     * 500 instead of a validation error.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($email = $this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($email))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // Password::defaults() keeps registration, reset and change in step
            // with one another — and with whatever the frontend tells the user.
            'password' => ['required', 'confirmed', Password::defaults()],
            'locale' => ['sometimes', Rule::in(UserSetting::LOCALES)],
        ];
    }
}
