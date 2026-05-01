<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('sketch_path')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'user_id', 'updated_at']);
        });

        Schema::create('game_note_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_note_id')->constrained('game_notes')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_note_attachments');
        Schema::dropIfExists('game_notes');
    }
};
