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
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('status');
            $table->timestamp('ended_at')->nullable()->after('started_at');
            $table->string('status_before_gm_disconnect', 20)->nullable()->after('ended_at');
            $table->timestamp('gm_grace_started_at')->nullable()->after('status_before_gm_disconnect');
            $table->timestamp('gm_grace_ends_at')->nullable()->after('gm_grace_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'started_at',
                'ended_at',
                'status_before_gm_disconnect',
                'gm_grace_started_at',
                'gm_grace_ends_at',
            ]);
        });
    }
};
