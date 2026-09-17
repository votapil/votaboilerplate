<?php

namespace App\Filament\Widgets;

use App\Enums\PermissionName;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Reference dashboard widget. Replace the numbers with yours; keep the two habits.
 *
 * HABIT 1 — pin the polling interval. Filament's default is '5s'
 * (vendor/filament/widgets/src/Concerns/CanPoll.php:7), so a dashboard left open on
 * somebody's second monitor re-runs every aggregate on it twelve times a minute against
 * the production database, forever. Nothing in the documentation hints at this. Every
 * widget in this project states its interval, or null to disable polling entirely.
 *
 * HABIT 2 — gate the widget itself. Resource policies do not cover widgets or custom
 * pages: whatever a widget renders is visible to everyone who can open the panel unless
 * canView() says otherwise. A permission that exists in the registry but is never checked
 * anywhere in the code is a promise the application does not keep.
 */
class UsersOverviewWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(PermissionName::AdminUsers->value);
    }

    /** @return array<Stat> */
    protected function getStats(): array
    {
        // One round trip for all three numbers. Three separate count() calls would be
        // three queries per poll, per open tab.
        $totals = User::query()
            ->selectRaw('COUNT(*) AS total_users')
            ->selectRaw('SUM(CASE WHEN email_verified_at IS NULL THEN 1 ELSE 0 END) AS unverified_users')
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS new_users', [now()->subWeek()])
            ->first();

        return [
            Stat::make(__('admin.widgets.users.total'), (int) ($totals->total_users ?? 0))
                ->description(__('admin.widgets.users.total_hint')),
            Stat::make(__('admin.widgets.users.unverified'), (int) ($totals->unverified_users ?? 0))
                ->description(__('admin.widgets.users.unverified_hint'))
                ->color('warning'),
            Stat::make(__('admin.widgets.users.new_week'), (int) ($totals->new_users ?? 0))
                ->description(__('admin.widgets.users.new_week_hint'))
                ->color('success'),
        ];
    }
}
