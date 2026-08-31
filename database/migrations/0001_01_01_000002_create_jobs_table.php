<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queue tables. `jobs` is the fallback for the `database` queue driver (the
 * default stack runs Redis + Horizon), but `failed_jobs` is used whatever the
 * driver is — that is where a job goes after its last retry, and it is the
 * first place to look when work silently stops happening.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('queue')->index()->comment('Queue name the job was pushed to');
            $table->longText('payload')->comment('Serialized job class and its constructor data');
            $table->unsignedTinyInteger('attempts')->comment('How many times a worker has picked this job up');
            $table->unsignedInteger('reserved_at')->nullable()->comment('Unix timestamp a worker claimed the job; null = waiting');
            $table->unsignedInteger('available_at')->comment('Unix timestamp the job may run; future value = delayed job');
            $table->unsignedInteger('created_at')->comment('Unix timestamp the job was queued');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Batch identifier (UUID)');
            $table->string('name')->comment('Human-readable batch name for the dashboard');
            $table->integer('total_jobs')->comment('Number of jobs the batch started with');
            $table->integer('pending_jobs')->comment('Jobs not finished yet');
            $table->integer('failed_jobs')->comment('Jobs that exhausted their retries');
            $table->longText('failed_job_ids')->comment('JSON array of failed job UUIDs');
            $table->mediumText('options')->nullable()->comment('Serialized batch callbacks and options');
            $table->integer('cancelled_at')->nullable()->comment('Unix timestamp the batch was cancelled; null = not cancelled');
            $table->integer('created_at')->comment('Unix timestamp the batch was dispatched');
            $table->integer('finished_at')->nullable()->comment('Unix timestamp all jobs settled; null = still running');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('uuid')->unique()->comment('Job UUID; the handle used to retry or forget a single failure');
            $table->text('connection')->comment('Queue connection the job ran on');
            $table->text('queue')->comment('Queue name the job ran on');
            $table->longText('payload')->comment('Serialized job, replayed as-is on retry');
            $table->longText('exception')->comment('Exception message and stack trace of the final attempt');
            $table->timestamp('failed_at')->useCurrent()->comment('When the job gave up');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
