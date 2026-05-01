<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_dice_rolls', function (Blueprint $table) {
            $table->string('random_source', 40)->default('unknown')->after('roll_values');
            $table->string('random_error')->nullable()->after('random_source');
        });
    }

    public function down(): void
    {
        Schema::table('session_dice_rolls', function (Blueprint $table) {
            $table->dropColumn(['random_source', 'random_error']);
        });
    }
};
