<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables of spatie/laravel-permission, published from the package and then
 * given a ->comment() on every column, as the project rule requires. Behaviour
 * is unchanged — if you ever re-publish the package migration, re-apply the
 * comments.
 *
 * The rows in these tables are the runtime truth of the access matrix;
 * App\Support\PermissionRegistry only supplies the defaults used the first time
 * a permission is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), 'Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('name')->comment('Permission name, e.g. admin.access; mirrors a case of App\Enums\PermissionName');
            $table->string('guard_name')->comment('Auth guard the permission belongs to; this application only uses "web"');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp');
            $table->timestamp('updated_at')->nullable()->comment('Last modification timestamp');

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->id()->comment('Primary key');
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable()->comment('Team the role is scoped to; null = global role');
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name')->comment('Role name, e.g. admin; see App\Support\PermissionRegistry::ROLES');
            $table->string('guard_name')->comment('Auth guard the role belongs to; this application only uses "web"');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp');
            $table->timestamp('updated_at')->nullable()->comment('Last modification timestamp');

            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission)->comment('FK to permissions.id');

            $table->string('model_type')->comment('Model class holding the permission directly, e.g. App\Models\User');
            $table->unsignedBigInteger($columnNames['model_morph_key'])->comment('Id of that model; polymorphic, so no foreign key');
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->comment('Team the grant is scoped to');
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole)->comment('FK to roles.id');

            $table->string('model_type')->comment('Model class holding the role, e.g. App\Models\User');
            $table->unsignedBigInteger($columnNames['model_morph_key'])->comment('Id of that model; polymorphic, so no foreign key');
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->comment('Team the grant is scoped to');
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission)->comment('FK to permissions.id');
            $table->unsignedBigInteger($pivotRole)->comment('FK to roles.id; a row here IS one cell of the access matrix');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(empty($tableNames), 'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
