<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserSettingUpdateRequest;
use App\Http\Resources\UserSettingResource;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;

/**
 * The current user's preferences. A singleton resource — one row per user —
 * so there is no index and no id in the path.
 */
final class UserSettingController extends Controller
{
    public function show(Request $request): UserSettingResource
    {
        return UserSettingResource::make($this->settingsFor($request->user()));
    }

    public function update(UserSettingUpdateRequest $request): UserSettingResource
    {
        $settings = $this->settingsFor($request->user());

        $settings->update($request->validated());

        return UserSettingResource::make($settings);
    }

    /**
     * Accounts can be born outside the registration flow — a seeder, an admin,
     * an OAuth callback added later — so never assume the row is there.
     */
    private function settingsFor(User $user): UserSetting
    {
        return $user->settings()->firstOrCreate([]);
    }
}
