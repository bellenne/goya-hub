<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_dice_rolls', function (Blueprint $table) {
            $table->string('source_type', 20)->nullable()->after('user_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('source_name')->nullable()->after('source_id');
            $table->integer('manual_modifier')->default(0)->after('modifier');
            $table->string('attribute_key')->nullable()->after('manual_modifier');
            $table->string('attribute_label')->nullable()->after('attribute_key');
            $table->integer('attribute_modifier')->default(0)->after('attribute_label');

            $table->index(['game_session_id', 'source_type', 'source_id'], 'session_dice_rolls_source_index');
        });
    }

    public function down(): void
    {
        Schema::table('session_dice_rolls', function (Blueprint $table) {
            $table->dropIndex('session_dice_rolls_source_index');
            $table->dropColumn([
                'source_type',
                'source_id',
                'source_name',
                'manual_modifier',
                'attribute_key',
                'attribute_label',
                'attribute_modifier',
            ]);
        });
    }
};
