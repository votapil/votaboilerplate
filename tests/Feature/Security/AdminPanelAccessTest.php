<?php

use App\Models\User;

/**
 * Regression guard: entering the admin panel is a permission, not a side effect of
 * being logged in.
 *
 * Filament decides panel access through User::canAccessPanel(). A stock model
 * returns true for everybody, so the day the panel goes up, every registered
 * account can open it — and from the Users resource, promote itself. That is how a
 * self-escalation hole survived five months in the project this template comes
 * from, until a manual audit found it.
 */
test('an ordinary account cannot enter the admin panel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

test('an account holding admin.access can enter the admin panel', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

test('a guest is sent to the panel login rather than into the panel', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
