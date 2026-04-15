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
        Schema::create('session_scene_state_npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_scene_state_id')->constrained()->cascadeOnDelete();
            $table->foreignId('npc_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['session_scene_state_id', 'npc_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_scene_state_npcs');
    }
};
