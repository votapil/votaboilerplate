<?php

namespace Tests\Support\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Model;

/**
 * Stand-in for "any entity a user owns", used by the multi-tenancy regression
 * guards. The template ships no domain entity, but the isolation machinery it
 * provides — the global owner scope, the scoped route binding, the ScopedExists
 * validation rule — is precisely the part that must never regress, so the guards
 * bring their own subject instead of waiting for the first real model.
 *
 * Its table is created per test by createOwnedItemsTable() (tests/Pest.php).
 */
class OwnedItem extends Model
{
    use BelongsToAuthUser;

    protected $table = 'owned_items';

    protected $guarded = [];

    public $timestamps = false;
}
