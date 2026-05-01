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
        if (! Schema::hasColumn('game_sessions', 'started_at')) {
            Schema::table('game_sessions', fn (Blueprint $table) => $table->timestamp('started_at')->nullable()->after('status'));
        }

        if (! Schema::hasColumn('game_sessions', 'ended_at')) {
            Schema::table('game_sessions', fn (Blueprint $table) => $table->timestamp('ended_at')->nullable()->after('started_at'));
        }

        if (! Schema::hasColumn('game_sessions', 'status_before_gm_disconnect')) {
            Schema::table('game_sessions', fn (Blueprint $table) => $table->string('status_before_gm_disconnect', 20)->nullable()->after('ended_at'));
        }

        if (! Schema::hasColumn('game_sessions', 'gm_grace_started_at')) {
            Schema::table('game_sessions', fn (Blueprint $table) => $table->timestamp('gm_grace_started_at')->nullable()->after('status_before_gm_disconnect'));
        }

        if (! Schema::hasColumn('game_sessions', 'gm_grace_ends_at')) {
            Schema::table('game_sessions', fn (Blueprint $table) => $table->timestamp('gm_grace_ends_at')->nullable()->after('gm_grace_started_at'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = collect([
                'started_at',
                'ended_at',
                'status_before_gm_disconnect',
                'gm_grace_started_at',
                'gm_grace_ends_at',
            ])
            ->filter(fn (string $column) => Schema::hasColumn('game_sessions', $column))
            ->values()
            ->all();

        if ($columns !== []) {
            Schema::table('game_sessions', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
