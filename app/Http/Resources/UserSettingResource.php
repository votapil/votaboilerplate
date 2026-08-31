<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserSetting
 */
final class UserSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'theme' => $this->theme,
        ];
    }
}
