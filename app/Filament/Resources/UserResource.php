<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Support\NavBadges;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The one worked example of a Filament resource in this template.
 *
 * HOW TO ADD A RESOURCE
 *
 *   1. Write the migration, every column carrying ->comment().
 *   2. `make artisan args="vota:crud Article"`      — model, request, resource, factory.
 *   3. `make artisan args="make:filament-resource Article --generate"` — Filament reads
 *      the actual table and scaffolds the form and table from real columns and comments.
 *
 * Step 3 gives you a skeleton, not a screen. Generated resources are what turned the
 * donor project's sidebar into thirty alphabetically-sorted tables with identical
 * placeholder icons. Four edits are mandatory before the resource is done:
 *
 *   (1) POLICY. Add App\Policies\ArticlePolicy extends BasePolicy. Without one, the
 *       resource is fully open to anyone who can enter the panel — Filament's default
 *       when no policy exists is "allowed".
 *   (2) PLACE IT. navigationGroup (a key from AdminPanelProvider::GROUP_*), a real icon,
 *       navigationSort. Skip this and the resource lands in a flat list with the stock
 *       rectangle icon.
 *   (3) WEED THE TABLE. --generate emits a column per database column. Keep the four or
 *       five somebody actually scans; push the rest behind
 *       ->toggleable(isToggledHiddenByDefault: true) or drop them.
 *   (4) FILTERS. At minimum the owner/status filters support will reach for. A table with
 *       no filters is a data dump, not a tool.
 *
 * AND ONLY WHEN YOU NEED IT. Generate a resource when there is a job to do in it, not
 * because a table exists. The donor project generated 33 resources for 55 models and then
 * spent a spec hiding the ones that duplicated each other or that nobody could explain.
 */
class UserResource extends Resource
{
    /**
     * Name of the role that owns access management.
     *
     * Kept as a constant rather than a literal because two guards depend on it: the
     * badge colour below and the last-administrator lock in Pages\EditUser. Keep it in
     * sync with database/seeders/RolesAndPermissionsSeeder.
     */
    public const ADMIN_ROLE = 'admin';

    protected static ?string $model = User::class;

    /*
    |--------------------------------------------------------------------------
    | (2) Place it in the sidebar
    |--------------------------------------------------------------------------
    */

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return AdminPanelProvider::GROUP_USERS;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.users.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.users.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.plural_label');
    }

    /**
     * Read from the cached aggregate — never a query of its own. Filament evaluates this
     * on every render of every page; see App\Filament\Support\NavBadges.
     */
    public static function getNavigationBadge(): ?string
    {
        return NavBadges::for('users_unverified');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('admin.users.badge_tooltip');
    }

    /** Top-bar search. Cheap to add, and it is how support finds one account. */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // columnSpanFull() on every Section is not decoration. Filament 4 dropped
            // the implicit full-width span sections used to have, so without it these
            // two land side by side in the schema's 2-column grid and the form reads
            // as two unrelated cards instead of one stacked flow.
            Section::make(__('admin.users.sections.profile'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('admin.users.fields.name'))
                        ->required()
                        ->maxLength(255),

                    // Lower-cased on write so an account created here matches what the
                    // API would have stored, and so the panel login (which lower-cases
                    // what was typed) can find it again.
                    TextInput::make('email')
                        ->label(__('admin.users.fields.email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                            ? mb_strtolower(trim($state))
                            : $state),

                    // Hashing is the model's 'hashed' cast, not this form's job.
                    // dehydrated() keeps an untouched field out of the update entirely,
                    // instead of overwriting the password with an empty string.
                    TextInput::make('password')
                        ->label(__('admin.users.fields.password'))
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText(__('admin.users.fields.password_hint')),

                    DateTimePicker::make('email_verified_at')
                        ->label(__('admin.users.fields.email_verified_at')),
                ]),

            Section::make(__('admin.users.sections.access'))
                ->columnSpanFull()
                ->schema([
                    // The privilege-escalation surface of the panel. It is reachable only
                    // through UserPolicy, and removing the last administrator's role is
                    // blocked in Pages\EditUser.
                    Select::make('roles')
                        ->label(__('admin.users.fields.roles'))
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->helperText(__('admin.users.fields.roles_hint', ['role' => self::ADMIN_ROLE])),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            /*
            |------------------------------------------------------------------
            | (3) A weeded table
            |------------------------------------------------------------------
            | What a human scans stays visible; the rest is toggleable. Note
            | ->with('roles'): the roles column reads a relation on every row, and
            | without eager loading that is a query per row.
            */
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('roles'))
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.users.fields.id'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label(__('admin.users.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('roles.name')
                    ->label(__('admin.users.fields.roles'))
                    ->badge()
                    ->color(fn (string $state): string => $state === self::ADMIN_ROLE ? 'danger' : 'gray'),

                IconColumn::make('email_verified_at')
                    ->label(__('admin.users.fields.email_verified_at'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('admin.users.fields.created_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('admin.users.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            /*
            |------------------------------------------------------------------
            | (4) Filters
            |------------------------------------------------------------------
            | Two things to know about filters:
            |
            | - They are the target of deep links. Another resource can link
            |   straight into a pre-filtered list with
            |   `UserResource::getUrl('index', ['tableFilters' => ['roles' => ...]])`,
            |   which is what turns a set of global tables into a support tool.
            |   The link fails SILENTLY if the target table has no filter under
            |   that name, so the filter and the link ship together.
            |
            | - Use ->searchable() and NOT ->preload() on a relationship filter
            |   whose table can grow (users, customers, orders). preload() loads
            |   the entire table into the select on every render.
            */
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label(__('admin.users.filters.verified'))
                    ->nullable(),

                SelectFilter::make('roles')
                    ->label(__('admin.users.filters.roles'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
