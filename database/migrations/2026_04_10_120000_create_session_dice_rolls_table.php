<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_dice_rolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('dice_count');
            $table->string('dice_type', 10);
            $table->integer('modifier')->default(0);
            $table->json('roll_values');
            $table->integer('total');
            $table->timestamps();

            $table->index(['game_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_dice_rolls');
    }
};
