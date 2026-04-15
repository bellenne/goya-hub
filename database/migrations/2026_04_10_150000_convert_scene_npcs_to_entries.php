<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_scene_state_npcs', function (Blueprint $table) {
            $table->dropUnique('session_scene_state_npcs_session_scene_state_id_npc_id_unique');
            $table->string('scene_type')->nullable()->after('npc_id');
            $table->string('display_name')->nullable()->after('scene_type');
            $table->boolean('is_group')->default(false)->after('display_name');
            $table->unsignedSmallInteger('group_size')->nullable()->after('is_group');
        });

        DB::statement(<<<'SQL'
            update session_scene_state_npcs
            set scene_type = npcs.type
            from npcs
            where session_scene_state_npcs.npc_id = npcs.id
        SQL);

        Schema::table('session_scene_states', function (Blueprint $table) {
            $table->foreignId('speaker_scene_npc_id')
                ->nullable()
                ->after('speaker_id')
                ->constrained('session_scene_state_npcs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('session_scene_states', function (Blueprint $table) {
            $table->dropConstrainedForeignId('speaker_scene_npc_id');
        });

        Schema::table('session_scene_state_npcs', function (Blueprint $table) {
            $table->dropColumn(['scene_type', 'display_name', 'is_group', 'group_size']);
            $table->unique(['session_scene_state_id', 'npc_id']);
        });
    }
};
