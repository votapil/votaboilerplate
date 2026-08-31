<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class PasswordForgotRequest extends FormRequest
{
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
        // Deliberately no `exists:users` rule: it would turn this endpoint into
        // an account-enumeration oracle.
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }
}
