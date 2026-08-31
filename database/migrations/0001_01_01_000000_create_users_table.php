<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every column carries a ->comment(). The database is the one description of
 * the domain that cannot drift from the code, and it is what both the schema
 * documentation and an unfamiliar reader (human or model) look at first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('name')->comment('Display name shown in the UI');
            $table->string('email')->unique()->comment('Login identifier; always stored lowercased, see App\Models\User::email()');
            $table->timestamp('email_verified_at')->nullable()->comment('When the address was confirmed; null = never verified');
            $table->string('password')->comment('Password hash (bcrypt/argon); never a readable value');
            $table->string('remember_token', 100)->nullable()->comment('"Remember me" token for the session guard');
            $table->timestamp('created_at')->nullable()->comment('Registration timestamp');
            $table->timestamp('updated_at')->nullable()->comment('Last modification timestamp');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('Address the reset link was requested for; one live token per address');
            $table->string('token')->comment('Hashed reset token; compared against the value in the emailed link');
            $table->timestamp('created_at')->nullable()->comment('Issued at; the broker expires tokens from this, see config/auth.php passwords.expire');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Session identifier stored in the client cookie');
            $table->foreignId('user_id')->nullable()->index()->comment('FK to users.id; null for guest sessions');
            $table->string('ip_address', 45)->nullable()->comment('Client IP at last activity; 45 chars fits IPv6');
            $table->text('user_agent')->nullable()->comment('Client User-Agent at last activity');
            $table->longText('payload')->comment('Serialized session data');
            $table->integer('last_activity')->index()->comment('Unix timestamp of last activity; drives garbage collection');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
