<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PermissionName;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property-read UserSetting|null $settings
 */
class User extends Authenticatable implements FilamentUser, HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Roles shipped with the template. Everything else about the access matrix
     * lives in the database and is edited from the admin panel — see
     * App\Support\PermissionRegistry for the defaults and why the seeder never
     * overwrites them.
     */
    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    /**
     * The only guard in the system.
     *
     * Pinning this is NOT redundant. config/auth.php declares a single `web`
     * guard; the `sanctum` guard never appears there — Sanctum injects it at
     * runtime (SanctumServiceProvider::register() merges
     * auth.guards.sanctum with 'provider' => null). Spatie resolves a model's
     * guard from that config and cannot match a provider-less guard, so without
     * this line roles and permissions granted through the API context drift
     * apart from the ones Filament sees over its session. Deleting it looks
     * like a cleanup and breaks authorization in a way that only shows up in
     * production.
     */
    protected string $guard_name = 'web';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Email addresses are case-insensitive identifiers. Store them lowercased
     * so the unique index, the login lookup and the password broker (which
     * queries the column verbatim) can never disagree about one account.
     */
    protected function email(): Attribute
    {
        return Attribute::set(
            fn (?string $value): ?string => $value === null ? null : mb_strtolower(trim($value))
        );
    }

    /**
     * Look a user up by email regardless of how it was typed — and regardless
     * of rows written before the lowercasing above existed.
     */
    public static function findByEmail(?string $email): ?self
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return static::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->first();
    }

    /**
     * Password reset mail.
     *
     * Two things have to be corrected for an API-only backend with a separate
     * frontend, and both are done through the framework's own hooks rather than
     * by subclassing ResetPassword. Subclassing is the obvious move and it has a
     * cost that only shows up later: the notification is then recorded under a
     * different class name, so every Notification::assertSentTo(ResetPassword)
     * in the test suite — and in any package that watches for it — stops
     * matching.
     *
     *  1. The default link is built from route('password.reset'), a web route
     *     this application does not define; the mail would lead to a 404.
     *  2. The default body comes from JSON translation keys, while this project
     *     keeps its wording in lang/{locale}/passwords.php.
     */
    public function sendPasswordResetNotification($token): void
    {
        ResetPassword::toMailUsing(fn (self $notifiable, string $resetToken): MailMessage => (new MailMessage)
            ->subject(__('passwords.reset_subject'))
            ->line(__('passwords.reset_line'))
            ->action(__('passwords.reset_action'), self::passwordResetUrl($notifiable, $resetToken))
            ->line(__('passwords.reset_ignore')));

        $this->notify(new ResetPassword($token));
    }

    private static function passwordResetUrl(self $notifiable, string $token): string
    {
        return rtrim((string) config('app.frontend_url'), '/')
            .'/password-reset?token='.$token
            .'&email='.urlencode($notifiable->getEmailForPasswordReset());
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Language for every mail and notification sent to this user.
     *
     * Implementing HasLocalePreference is the correct way to localize outgoing
     * messages: Laravel wraps the whole send in withLocale(), so plain __()
     * calls inside a notification resolve in the recipient's language even when
     * the job runs on a queue worker with no request behind it.
     *
     * Do NOT reach for $mailMessage->locale() instead — MailMessage has no such
     * method, and the resulting BadMethodCallException surfaces as a 500 in
     * production rather than as a broken translation.
     */
    public function preferredLocale(): ?string
    {
        // loadMissing(), not a bare $this->settings: Model::shouldBeStrict()
        // turns lazy loading into an exception outside production, and this runs
        // on a user the framework just fetched by email.
        return $this->loadMissing('settings')->settings?->locale;
    }

    /**
     * Admin panel gate. Checking a permission rather than "is authenticated" is
     * the whole point: with the stock `return true` every registered user can
     * open /admin, and every Filament resource without a policy is then wide
     * open to them.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can(PermissionName::AdminAccess->value);
    }
}
