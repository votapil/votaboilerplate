<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Database\Factories\UserSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-user preferences.
 *
 * They live in their own table instead of extra columns on `users` because the
 * list only ever grows, and because "one row per user" keeps the auth table —
 * the hottest table in the app — narrow. Language, timezone and theme are the
 * three every product needs and the three that are painful to retrofit: by the
 * time you want them, dates are already rendered in UTC and the locale already
 * lives in a cookie.
 *
 * @property int $id
 * @property int $user_id
 * @property string $locale
 * @property string $timezone
 * @property string $theme
 */
class UserSetting extends Model
{
    /** @use HasFactory<UserSettingFactory> */
    use BelongsToAuthUser, HasFactory;

    /**
     * Locales the application ships translations for. Adding one means adding
     * lang/<code>/ on the backend and the matching messages on the frontend —
     * a half-translated locale is worse than a missing one.
     *
     * @var list<string>
     */
    public const LOCALES = ['en', 'ru'];

    /** @var list<string> */
    public const THEMES = ['light', 'dark', 'system'];

    /**
     * Mirrors the column defaults in the migration so a settings row created
     * from code is fully populated in memory, without a re-read.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'locale' => 'en',
        'timezone' => 'UTC',
        'theme' => 'system',
    ];

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'locale',
        'timezone',
        'theme',
    ];
}
