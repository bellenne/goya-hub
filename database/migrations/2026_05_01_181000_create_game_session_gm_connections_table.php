<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_session_gm_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('connection_id', 80);
            $table->timestamp('connected_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'connection_id']);
            $table->index(['game_session_id', 'disconnected_at', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_session_gm_connections');
    }
};
