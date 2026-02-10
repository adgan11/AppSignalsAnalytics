<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->uuid('event_id');
            $table->string('event_name', 100);
            $table->string('user_id', 255)->nullable();
            $table->string('device_id', 255);
            $table->string('session_id', 255);
            $table->json('properties')->nullable();

            // Context columns
            $table->string('os_version', 20)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->char('country_code', 2)->nullable();

            // Timestamps
            $table->timestamp('event_timestamp', 3); // Millisecond precision
            $table->timestamp('received_at', 3);

            // Indexes for common queries
            $table->index(['project_id', 'event_name', 'event_timestamp']);
            $table->index(['project_id', 'event_timestamp']);
            $table->index('session_id');
            $table->index(['project_id', 'user_id']);
        });

        Schema::create('daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('event_name', 100);

            // Metrics
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('unique_users')->default(0);
            $table->unsignedInteger('unique_devices')->default(0);
            $table->unsignedInteger('unique_sessions')->default(0);

            // Dimensions (nullable for aggregate totals)
            $table->char('country_code', 2)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('app_version', 50)->nullable();

            $table->timestamps();

            // Unique constraint for upserts
            $table->unique([
                'project_id',
                'date',
                'event_name',
                'country_code',
                'device_model',
                'app_version'
            ], 'daily_stats_unique');

            $table->index(['project_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_stats');
        Schema::dropIfExists('events');
    }
};
