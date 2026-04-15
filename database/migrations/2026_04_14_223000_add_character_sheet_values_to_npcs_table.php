<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->json('attribute_values')->nullable()->after('uses_character_sheet');
            $table->json('skill_values')->nullable()->after('attribute_values');
            $table->json('extra_field_values')->nullable()->after('skill_values');
        });
    }

    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn(['attribute_values', 'skill_values', 'extra_field_values']);
        });
    }
};
