<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sanctum's token store. Published here rather than left to
 * `vendor:publish` because the API cannot authenticate a single request
 * without it, and a missing table shows up as a confusing 500 on login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('tokenable_type')->comment('Model class the token belongs to, e.g. App\Models\User');
            $table->unsignedBigInteger('tokenable_id')->comment('Id of that model; polymorphic, so no foreign key');
            $table->text('name')->comment('Label given at creation, e.g. "api"; the only audit trail a token has');
            $table->string('token', 64)->unique()->comment('SHA-256 of the plaintext token; the plaintext is shown once and never stored');
            $table->text('abilities')->nullable()->comment('JSON array of granted abilities; ["*"] means unrestricted');
            $table->timestamp('last_used_at')->nullable()->comment('Last request authenticated with this token');
            $table->timestamp('expires_at')->nullable()->index()->comment('Hard expiry; null = never, see config/sanctum.php expiration');
            $table->timestamp('created_at')->nullable()->comment('Issued at');
            $table->timestamp('updated_at')->nullable()->comment('Last modification timestamp');

            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
