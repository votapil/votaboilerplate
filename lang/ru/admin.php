<?php

/*
|--------------------------------------------------------------------------
| Admin panel strings (ru)
|--------------------------------------------------------------------------
|
| Key-for-key mirror of lang/en/admin.php. See that file for the rules: the
| panel is a staff tool with its own single namespace, a panel label never goes
| into a user-facing file, and both files change in the same commit.
|
*/

return [

    // Sidebar groups. Keyed by the identifiers in AdminPanelProvider::GROUP_*,
    // which resources return from getNavigationGroup().
    'groups' => [
        'users' => 'Пользователи',
        'system' => 'Система',
        'access' => 'Доступы',
    ],

    'users' => [
        'label' => 'Пользователь',
        'plural_label' => 'Пользователи',
        'navigation_label' => 'Пользователи',
        'badge_tooltip' => 'Аккаунты с неподтверждённой почтой',

        'sections' => [
            'profile' => 'Профиль',
            'access' => 'Доступы',
        ],

        'fields' => [
            'id' => 'ID',
            'name' => 'Имя',
            'email' => 'Email',
            'password' => 'Новый пароль',
            'password_hint' => 'Оставьте пустым, чтобы не менять пароль.',
            'email_verified_at' => 'Почта подтверждена',
            'roles' => 'Роли',
            'roles_hint' => 'Роль несёт права. Выдача роли :role передаёт полный контроль над панелью.',
            'created_at' => 'Регистрация',
            'updated_at' => 'Обновлён',
        ],

        'filters' => [
            'verified' => 'Почта подтверждена',
            'roles' => 'Роль',
        ],

        'last_admin' => [
            'title' => 'Это последний администратор',
            'body' => 'Роль :role снять нельзя — управлять доступами станет некому.',
            'delete_title' => 'Нельзя удалить последнего администратора',
            'delete_body' => 'Сначала передайте роль :role другому аккаунту, потом удаляйте этот.',
        ],
    ],

    'widgets' => [
        'users' => [
            'total' => 'Пользователи',
            'total_hint' => 'аккаунтов всего',
            'unverified' => 'Почта не подтверждена',
            'unverified_hint' => 'так и не подтвердили адрес',
            'new_week' => 'Новые за неделю',
            'new_week_hint' => 'зарегистрировались за 7 дней',
        ],
    ],

];
