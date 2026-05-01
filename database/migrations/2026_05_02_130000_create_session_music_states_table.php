<?php

use App\Enums\SessionMusicPlaybackStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_music_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->unique()->constrained('game_sessions')->cascadeOnDelete();
            $table->string('source_type', 40)->nullable();
            $table->string('title')->nullable();
            $table->string('file_path')->nullable();
            $table->text('direct_url')->nullable();
            $table->text('youtube_url')->nullable();
            $table->string('playback_status', 40)->default(SessionMusicPlaybackStatus::Stopped->value);
            $table->unsignedInteger('position_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_music_states');
    }
};
