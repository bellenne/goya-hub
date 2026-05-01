<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameSession;
use App\Services\Sessions\TrackGmSessionPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GameSessionGmPresenceController extends Controller
{
    public function connect(Request $request, Game $game, GameSession $session, TrackGmSessionPresence $presence): JsonResponse
    {
        $this->authorizePresence($request, $game, $session);

        $session = $presence->connect($session, $request->user(), $this->connectionId($request));

        return response()->json($this->payload($session));
    }

    public function heartbeat(Request $request, Game $game, GameSession $session, TrackGmSessionPresence $presence): JsonResponse
    {
        $this->authorizePresence($request, $game, $session);

        $session = $presence->heartbeat($session, $request->user(), $this->connectionId($request));

        return response()->json($this->payload($session));
    }

    public function disconnect(Request $request, Game $game, GameSession $session, TrackGmSessionPresence $presence): JsonResponse
    {
        $this->authorizePresence($request, $game, $session);

        $presence->disconnect($session, $this->connectionId($request));

        return response()->json(['ok' => true]);
    }

    protected function authorizePresence(Request $request, Game $game, GameSession $session): void
    {
        abort_unless($session->game_id === $game->id, 404);
        Gate::authorize('start', $session);

        $request->validate([
            'connection_id' => ['required', 'string', 'max:80'],
        ]);
    }

    protected function connectionId(Request $request): string
    {
        return (string) $request->input('connection_id');
    }

    protected function payload(GameSession $session): array
    {
        return [
            'session_id' => $session->id,
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'gm_grace_started_at' => $session->gm_grace_started_at?->toISOString(),
            'gm_grace_ends_at' => $session->gm_grace_ends_at?->toISOString(),
            'ended_at' => $session->ended_at?->toISOString(),
            'heartbeat_interval_seconds' => TrackGmSessionPresence::HEARTBEAT_INTERVAL_SECONDS,
            'heartbeat_stale_after_seconds' => TrackGmSessionPresence::HEARTBEAT_STALE_AFTER_SECONDS,
            'grace_period_seconds' => TrackGmSessionPresence::GRACE_PERIOD_SECONDS,
        ];
    }
}
