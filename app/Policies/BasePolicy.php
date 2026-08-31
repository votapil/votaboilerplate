<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for every panel policy: one coarse section permission gates every ability.
 *
 * WHY A BASE CLASS. Filament asks a Laravel policy before it shows a resource, a row
 * action or a bulk action, so a resource without a policy is readable and writable by
 * anyone who got through the panel door. The obvious fix — a policy per resource — turns
 * into a copy-paste farm: the donor project ended up with 35 policies, 31 of them
 * byte-identical apart from one permission name, 1850 lines that nobody could review.
 * Here a policy is the permission plus whatever it genuinely needs on top.
 *
 * WHY COARSE PERMISSIONS. One permission per SECTION of the panel ("users", "system"),
 * not per model per verb. A per-model CRUD matrix produces hundreds of permissions that
 * nobody can hold in their head and that drift out of sync with the code the moment a
 * model is renamed.
 *
 * HOW TO ADD ONE:
 *
 *     final class ArticlePolicy extends BasePolicy
 *     {
 *         protected function permission(): PermissionName
 *         {
 *             return PermissionName::AdminContent;
 *         }
 *     }
 *
 * Laravel discovers App\Policies\{Model}Policy for App\Models\{Model} on its own — no
 * registration needed. A policy for a model outside App\Models (a package model, say)
 * has to be registered explicitly with Gate::policy().
 *
 * Override a single method when a section has a real rule (see UserPolicy). Do not
 * override to widen access: if a section needs different access, it needs its own
 * permission.
 */
abstract class BasePolicy
{
    /** The single permission that gates this section of the panel. */
    abstract protected function permission(): PermissionName;

    protected function allowed(User $user): bool
    {
        return $user->can($this->permission()->value);
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function replicate(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk and table-wide abilities
    |--------------------------------------------------------------------------
    |
    | Filament consults these by name for bulk actions and reordering
    | (vendor/filament/filament/src/Resources/Resource.php:250-305). A policy that
    | omits them does not fail loudly — the buttons just quietly never appear.
    |
    */

    public function deleteAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function reorder(User $user): bool
    {
        return $this->allowed($user);
    }
}
