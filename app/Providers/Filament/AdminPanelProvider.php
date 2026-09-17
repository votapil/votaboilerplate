<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /*
    |----------------------------------------------------------------------
    | Sidebar groups
    |----------------------------------------------------------------------
    |
    | A resource returns one of these keys from getNavigationGroup(); the human
    | label is resolved from lang/{locale}/admin.php at render time (see
    | navigationGroups() below). Filament matches a resource's group against the
    | registered ARRAY KEY first and the label second
    | (vendor/filament/filament/src/Navigation/NavigationManager.php:81), which
    | is what lets the label be translated without breaking the grouping.
    |
    | Every resource MUST name a group. Without one Filament drops it into a
    | flat alphabetical list — the donor project reached 30 ungrouped resources
    | with placeholder icons before anyone noticed, and untangling that cost a
    | whole spec.
    |
    */
    public const GROUP_USERS = 'users';

    public const GROUP_SYSTEM = 'system';

    public const GROUP_ACCESS = 'access';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')

            // Optional dedicated host for the panel (ADMIN_DOMAIN). Serving the
            // panel from its own hostname keeps Filament's cookie/session auth
            // away from the token-authenticated SPA. Null = no host constraint,
            // panel lives at /admin on the main domain. Note the trade-off: with
            // a domain set, /admin on the main domain stops resolving.
            ->domain(config('app.admin_domain') ?: null)

            // Pinned explicitly. Sanctum injects an `auth.guards.sanctum` entry at
            // runtime with provider => null; leaving the guard implicit is how a
            // project ends up authenticating the panel against a guard that
            // spatie/laravel-permission cannot map to a user provider.
            ->authGuard('web')

            // Custom login page: normalizes the email before the guard compares it
            // to the column. See App\Filament\Auth\Login.
            ->login(Login::class)

            // REQUIRES the `notifications` table (php artisan make:notifications-table).
            // Filament queries it on every panel render; without the table the whole
            // panel 500s. Polling is pinned because the framework default is 30s.
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')

            ->colors([
                'primary' => Color::Amber,
            ])

            // Order of this array = order of the sidebar. Working sections on top,
            // reference/system sections collapsed. Labels are closures so they are
            // resolved per render instead of being frozen at boot — under Octane the
            // provider registers once and would otherwise cache the boot locale.
            ->navigationGroups([
                self::GROUP_USERS => NavigationGroup::make(fn (): string => __('admin.groups.users')),
                self::GROUP_SYSTEM => NavigationGroup::make(fn (): string => __('admin.groups.system'))->collapsed(),
                self::GROUP_ACCESS => NavigationGroup::make(fn (): string => __('admin.groups.access'))->collapsed(),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])

            // Widgets are discovered, not listed. Filament's own AccountWidget and
            // FilamentInfoWidget are deliberately NOT registered: they are a demo of
            // the framework, not of this application, and they are the first thing
            // every reviewer asks about. Every widget you add must set
            // $pollingInterval explicitly — see App\Filament\Widgets.
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            // Who may open the panel at all is decided by User::canAccessPanel(),
            // NOT here: Filament's Authenticate middleware calls it when the user
            // implements Filament\Models\Contracts\FilamentUser, and otherwise falls
            // back to `abort_if(config('app.env') !== 'local')`
            // (vendor/filament/filament/src/Http/Middleware/Authenticate.php:35).
            // That fallback is a two-sided trap: locally the panel is open to EVERY
            // registered account, and in production it is shut to everyone including
            // you. So App\Models\User must implement FilamentUser and gate on the
            // panel-access permission.
            //
            // What a user may then DO inside the panel is decided per resource by a
            // Laravel policy (App\Policies\BasePolicy). A resource without a policy
            // is open to anyone who got through this door.
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
