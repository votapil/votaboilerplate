<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Panel login that treats the e-mail as a case-insensitive identifier.
 *
 * The API stores addresses lower-cased, but Filament's stock login hands the typed string
 * to the guard, which compares it to the column verbatim. Result: an administrator whose
 * address is "Sam@Example.com" registers fine and then cannot log into the panel, with no
 * error that points at capitalisation. Eighteen lines, one afternoon of debugging.
 *
 * The other half of the fix is on the way in: UserResource lower-cases the e-mail before
 * it is written, so panel-created accounts match what the API would have stored.
 *
 * Deliberately NOT in app/Filament/Pages — that directory is scanned by discoverPages(),
 * which would register the login screen as an ordinary page in the sidebar.
 */
class Login extends BaseLogin
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => mb_strtolower(trim((string) $data['email'])),
            'password' => $data['password'],
        ];
    }
}
