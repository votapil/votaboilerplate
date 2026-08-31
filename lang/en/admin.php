<?php

/*
|--------------------------------------------------------------------------
| Admin panel strings
|--------------------------------------------------------------------------
|
| The ONE namespace the Filament panel reads. Decide the panel's language here,
| up front, so it never has to be re-argued in review.
|
| The decision this template ships with: the panel is a STAFF tool and sits
| outside the user-facing locale mandate. User-facing text (API messages,
| validation, mail, bot) must exist in every app locale; panel text only has to
| exist in the locales your staff actually reads. Both files are kept in key
| parity anyway, because a half-translated panel is worse than an English one
| and the parity test is free.
|
| Rules:
|   - A panel label NEVER goes into a user-facing file, and a user-facing
|     string NEVER goes here. If a string is shown to a customer, it belongs in
|     the app's own namespace.
|   - Add a key here and to lang/ru/admin.php in the same commit. The suite has
|     a key-parity test; a one-sided key fails the build, not production.
|   - Never hardcode a label in app/Filament/**. The alternative — what the
|     donor project ended up with — was 57 panel files carrying hardcoded
|     labels and not a single __() call, which makes the language choice
|     impossible to reverse.
|
| Note: Filament's own chrome (buttons, pagination, empty states) is translated
| by the package and follows app()->getLocale(); only your labels live here.
|
*/

return [

    // Sidebar groups. Keyed by the identifiers in AdminPanelProvider::GROUP_*,
    // which resources return from getNavigationGroup().
    'groups' => [
        'users' => 'Users',
        'system' => 'System',
        'access' => 'Access',
    ],

    'users' => [
        'label' => 'User',
        'plural_label' => 'Users',
        'navigation_label' => 'Users',
        'badge_tooltip' => 'Accounts with an unconfirmed email',

        'sections' => [
            'profile' => 'Profile',
            'access' => 'Access',
        ],

        'fields' => [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'New password',
            'password_hint' => 'Leave empty to keep the current password.',
            'email_verified_at' => 'Email confirmed',
            'roles' => 'Roles',
            'roles_hint' => 'Roles carry permissions. Granting :role hands over full control of the panel.',
            'created_at' => 'Registered',
            'updated_at' => 'Updated',
        ],

        'filters' => [
            'verified' => 'Email confirmed',
            'roles' => 'Role',
        ],

        'last_admin' => [
            'title' => 'This is the last administrator',
            'body' => 'The :role role cannot be taken away here — nobody would be left who can manage access.',
            'delete_title' => 'The last administrator cannot be deleted',
            'delete_body' => 'Hand the :role role to somebody else first, then delete this account.',
        ],
    ],

    'widgets' => [
        'users' => [
            'total' => 'Users',
            'total_hint' => 'accounts in total',
            'unverified' => 'Unconfirmed email',
            'unverified_hint' => 'never confirmed their address',
            'new_week' => 'New this week',
            'new_week_hint' => 'registered in the last 7 days',
        ],
    ],

];
