<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->boolean('uses_character_sheet')->default(false)->after('description');
        });

        DB::table('npcs')
            ->whereNotNull('stats')
            ->update(['uses_character_sheet' => true]);
    }

    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn('uses_character_sheet');
        });
    }
};
