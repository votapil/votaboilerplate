<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The shape of a user on the wire. Every endpoint that returns a user returns
 * THIS — one place to change when a field is added, and no chance of the login
 * response and the profile response drifting apart.
 *
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // Both lists come from Spatie relations. On a single user that is
            // two queries; in a COLLECTION it is two queries PER ROW, so any
            // endpoint listing users must ->with('roles', 'permissions') first.
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $this->getAllPermissions()->pluck('name')->sort()->values(),
            // Not whenLoaded(): a user created outside the registration flow
            // may have no settings row at all, and whenLoaded() would hand the
            // nested resource a null to read properties off.
            'settings' => $this->when(
                $this->relationLoaded('settings') && $this->settings !== null,
                fn () => UserSettingResource::make($this->settings),
            ),
        ];
    }
}
