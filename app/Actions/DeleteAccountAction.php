<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Permanently delete an account and everything hanging off it.
 *
 * Self-service deletion is not optional: the App Store, Google Play and the
 * GDPR all require it, and it is far cheaper to have the skeleton from day one
 * than to invent it the week before a store review.
 *
 * Order matters. Rows attached to the user through a polymorphic relation
 * (tokens, notifications) have no foreign key, so nothing deletes them for you
 * — they would be left pointing at a user id that no longer exists, and a
 * recycled id would inherit them. Rows attached by a real foreign key
 * (user_settings and whatever you add later) disappear via cascadeOnDelete.
 *
 * Anything stored OUTSIDE the database — uploaded files, external accounts —
 * must be collected before the rows go, since the paths live in those rows.
 */
final class DeleteAccountAction
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->notifications()->delete();

            $user->delete();
        });
    }
}
