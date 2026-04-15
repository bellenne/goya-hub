<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_scene_state_npcs', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->default(1)->after('is_present');
        });
    }

    public function down(): void
    {
        Schema::table('session_scene_state_npcs', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
