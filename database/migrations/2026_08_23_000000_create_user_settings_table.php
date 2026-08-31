<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user preferences.
 *
 * A separate table, not extra columns on `users`: the list only grows, and the
 * auth table is queried on every single request. The unique key on user_id
 * makes the one-to-one relationship a database fact rather than a convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete()
                ->comment('FK to users.id, one row per user; removed together with the account');
            $table->string('locale', 5)->default('en')->comment('UI and notification language, must be one of App\Models\UserSetting::LOCALES');
            $table->string('timezone', 64)->default('UTC')->comment('IANA timezone identifier used to render dates for this user');
            $table->string('theme', 10)->default('system')->comment('UI theme: light, dark, system');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp');
            $table->timestamp('updated_at')->nullable()->comment('Last modification timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
