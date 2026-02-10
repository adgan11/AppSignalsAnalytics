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
        Schema::create('session_replays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('session_id', 255);
            $table->string('user_id', 255)->nullable();
            $table->timestamp('started_at', 3);
            $table->timestamp('ended_at', 3)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('screen_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'started_at']);
            $table->unique(['project_id', 'session_id']);
        });

        Schema::create('replay_frames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_replay_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('chunk_index');
            $table->enum('frame_type', ['full', 'delta']);
            $table->binary('wireframe_data'); // BLOB for compressed data
            $table->timestamp('timestamp', 3);

            // Indexes
            $table->index(['session_replay_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replay_frames');
        Schema::dropIfExists('session_replays');
    }
};
