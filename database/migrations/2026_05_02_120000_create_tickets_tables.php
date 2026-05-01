<?php

use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('status', 40)->default(TicketStatus::Open->value);
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('last_message_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status', 'updated_at']);
            $table->index(['game_id', 'creator_user_id', 'updated_at']);
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};
