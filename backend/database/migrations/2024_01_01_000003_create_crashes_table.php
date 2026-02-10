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
        Schema::create('crashes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->uuid('crash_id');
            $table->string('crash_group_hash', 64);

            $table->string('user_id', 255)->nullable();
            $table->string('device_id', 255);
            $table->string('session_id', 255)->nullable();

            // Crash details
            $table->string('exception_type', 100);
            $table->text('exception_message')->nullable();
            $table->mediumText('stack_trace');

            // Symbolication
            $table->boolean('is_symbolicated')->default(false);
            $table->mediumText('symbolicated_trace')->nullable();

            // Context
            $table->string('os_version', 20)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('app_version', 50);
            $table->string('app_build', 50)->nullable();

            $table->timestamp('occurred_at', 3);
            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'crash_group_hash']);
            $table->index(['project_id', 'occurred_at']);
            $table->index(['project_id', 'is_symbolicated']);
        });

        Schema::create('dsym_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('uuid', 36);
            $table->string('app_version', 50);
            $table->string('build_number', 50);
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->unique(['project_id', 'uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dsym_files');
        Schema::dropIfExists('crashes');
    }
};
