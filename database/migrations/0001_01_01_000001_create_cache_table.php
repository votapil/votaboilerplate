<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fallback store for the `database` cache driver. The default stack uses Redis,
 * so these tables normally stay empty — keep them anyway: they are what the
 * application falls back to when Redis is not available, for instance in a
 * one-off container running migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary()->comment('Cache key, already prefixed by config/cache.php');
            $table->mediumText('value')->comment('Serialized cached value');
            $table->integer('expiration')->index()->comment('Unix timestamp when the entry expires');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary()->comment('Lock name, e.g. the key passed to Cache::lock()');
            $table->string('owner')->comment('Random token of the process holding the lock; only it may release');
            $table->integer('expiration')->index()->comment('Unix timestamp when the lock self-releases');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
