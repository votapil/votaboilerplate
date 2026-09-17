<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Support\NavBadges;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

/**
 * Last-administrator lock.
 *
 * The moment the role matrix becomes editable from the UI, a new failure mode exists:
 * the installation can be left with nobody able to manage access, and there is no way
 * back in short of a database console. Two locks close it — the role cannot be taken
 * away from the last administrator, and that account cannot be deleted.
 *
 * Deliberately enforced here, in the pages that perform the writes, rather than through
 * a Gate::before that hands administrators everything unconditionally. Gate::before would
 * make the invariant untestable: the very check you want to assert would always pass.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeSave(): void
    {
        if (! array_key_exists('roles', $this->data)) {
            return;
        }

        $adminRoleId = $this->adminRoleId();

        if ($adminRoleId === null || ! $this->isLastAdmin()) {
            return;
        }

        // The multi-select submits role keys, so compare ids and not names.
        $submittedRoleIds = array_map('intval', (array) $this->data['roles']);

        if (in_array($adminRoleId, $submittedRoleIds, true)) {
            return;
        }

        Notification::make()
            ->title(__('admin.users.last_admin.title'))
            ->body(__('admin.users.last_admin.body', ['role' => UserResource::ADMIN_ROLE]))
            ->danger()
            ->send();

        $this->halt();
    }

    /** The sidebar badge counts unverified accounts, and this form can change that. */
    protected function afterSave(): void
    {
        NavBadges::flush();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action): void {
                    if (! $this->isLastAdmin()) {
                        return;
                    }

                    Notification::make()
                        ->title(__('admin.users.last_admin.delete_title'))
                        ->body(__('admin.users.last_admin.delete_body', ['role' => UserResource::ADMIN_ROLE]))
                        ->danger()
                        ->send();

                    $action->halt();
                }),
        ];
    }

    /** Null when the role has not been seeded yet — a fresh install has nothing to protect. */
    private function adminRoleId(): ?int
    {
        $id = Role::query()
            ->where('name', UserResource::ADMIN_ROLE)
            ->where('guard_name', 'web')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private function isLastAdmin(): bool
    {
        // Checked first: Spatie's role() scope throws on a role name that does not exist.
        if ($this->adminRoleId() === null) {
            return false;
        }

        return $this->getRecord()->hasRole(UserResource::ADMIN_ROLE)
            && User::role(UserResource::ADMIN_ROLE)->count() === 1;
    }
}
