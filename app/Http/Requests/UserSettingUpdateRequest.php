<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\UserSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UserSettingUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', Rule::in(UserSetting::LOCALES)],
            // The `timezone` rule accepts any IANA identifier, which is what
            // date formatting needs; a free-form string here means every date
            // the user sees is silently wrong.
            'timezone' => ['sometimes', 'timezone'],
            'theme' => ['sometimes', Rule::in(UserSetting::THEMES)],
        ];
    }
}
