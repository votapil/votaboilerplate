<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The user list is the privilege-escalation surface of the whole panel: whoever can open
 * it can hand themselves any role. It therefore has its own permission, granted to
 * administrators only — a moderator who legitimately enters the panel for content work
 * must not reach it. That exact hole (no UserPolicy at all, so Filament defaulted to
 * "allowed") is what a security audit found in the donor project.
 *
 * This is also the reference for extending BasePolicy: name the permission, then override
 * only the abilities that carry a real rule.
 */
final class UserPolicy extends BasePolicy
{
    protected function permission(): PermissionName
    {
        return PermissionName::AdminUsers;
    }

    /**
     * Nobody deletes their own account from the admin list. Deleting yourself logs you
     * out mid-request and, if you were the last administrator, leaves the installation
     * with no way back in. The same invariant is enforced for role removal on the edit
     * page (App\Filament\Resources\UserResource\Pages\EditUser).
     */
    public function delete(User $user, Model $record): bool
    {
        return parent::delete($user, $record) && $user->isNot($record);
    }
}
