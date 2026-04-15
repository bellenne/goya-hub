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
        Schema::table('session_scene_state_npcs', function (Blueprint $table) {
            $table->boolean('is_encountered')->default(true)->after('npc_id');
            $table->boolean('is_present')->default(true)->after('is_encountered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_scene_state_npcs', function (Blueprint $table) {
            $table->dropColumn(['is_encountered', 'is_present']);
        });
    }
};
