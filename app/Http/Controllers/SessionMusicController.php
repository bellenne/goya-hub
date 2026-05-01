<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Enums\SessionMusicPlaybackStatus;
use App\Enums\SessionMusicSourceType;
use App\Events\SessionSceneUpdated;
use App\Http\Requests\UpdateSessionMusicPlaybackRequest;
use App\Http\Requests\UpdateSessionMusicSourceRequest;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\SessionMusicState;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SessionMusicController extends Controller
{
    public function updateSource(UpdateSessionMusicSourceRequest $request, Game $game, GameSession $session): JsonResponse
    {
        $this->authorizeSession($game, $session);

        $validated = $request->validated();
        $sourceType = SessionMusicSourceType::from($validated['source_type']);
        $musicState = $this->stateFor($session);
        $oldFilePath = $musicState->file_path;

        $payload = [
            'source_type' => $sourceType,
            'title' => $validated['title'] ?? null,
            'file_path' => null,
            'direct_url' => null,
            'youtube_url' => null,
            'playback_status' => SessionMusicPlaybackStatus::Stopped,
            'position_seconds' => 0,
            'started_at' => null,
        ];

        if ($sourceType === SessionMusicSourceType::Uploaded) {
            $payload['file_path'] = $request->file('track')->store('session-music', 'public');
            $payload['title'] = $payload['title'] ?: pathinfo($request->file('track')->getClientOriginalName(), PATHINFO_FILENAME);
        } elseif ($sourceType === SessionMusicSourceType::DirectUrl) {
            $payload['direct_url'] = $validated['direct_url'];
            $payload['title'] = $payload['title'] ?: $validated['direct_url'];
        } else {
            $payload['youtube_url'] = $validated['youtube_url'];
            $payload['title'] = $payload['title'] ?: 'YouTube track';
        }

        $musicState->update($payload);

        if ($oldFilePath && $oldFilePath !== $musicState->file_path) {
            Storage::disk('public')->delete($oldFilePath);
        }

        broadcast(new SessionSceneUpdated($session->fresh()))->toOthers();

        return response()->json([
            'music' => $this->payload($musicState->refresh()),
        ]);
    }

    public function updatePlayback(UpdateSessionMusicPlaybackRequest $request, Game $game, GameSession $session): JsonResponse
    {
        $this->authorizeSession($game, $session);

        $musicState = $this->stateFor($session);
        $status = SessionMusicPlaybackStatus::from($request->validated('playback_status'));
        $position = (int) $request->input('position_seconds', $this->currentPosition($musicState));

        $musicState->update([
            'playback_status' => $status,
            'position_seconds' => $status === SessionMusicPlaybackStatus::Stopped ? 0 : $position,
            'started_at' => $status === SessionMusicPlaybackStatus::Playing ? now() : null,
        ]);

        broadcast(new SessionSceneUpdated($session->fresh()))->toOthers();

        return response()->json([
            'music' => $this->payload($musicState->refresh()),
        ]);
    }

    protected function authorizeSession(Game $game, GameSession $session): void
    {
        abort_unless($session->game_id === $game->id, 404);
        Gate::authorize('start', $session);
        abort_unless(
            $session->status === SessionStatus::Active
                || (
                    $session->status === SessionStatus::GmDisconnectedGrace
                    && $session->status_before_gm_disconnect === SessionStatus::Active->value
                ),
            409,
        );
    }

    protected function stateFor(GameSession $session): SessionMusicState
    {
        return $session->musicState()->firstOrCreate([
            'game_session_id' => $session->id,
        ], [
            'playback_status' => SessionMusicPlaybackStatus::Stopped,
            'position_seconds' => 0,
        ]);
    }

    protected function currentPosition(SessionMusicState $musicState): int
    {
        $position = (int) $musicState->position_seconds;

        if ($musicState->playback_status === SessionMusicPlaybackStatus::Playing && $musicState->started_at !== null) {
            $position += (int) $musicState->started_at->diffInSeconds(now());
        }

        return max(0, $position);
    }

    protected function payload(SessionMusicState $musicState): array
    {
        return [
            'source_type' => $musicState->source_type?->value,
            'title' => $musicState->title,
            'audio_url' => $musicState->file_path ? Storage::disk('public')->url($musicState->file_path) : $musicState->direct_url,
            'youtube_url' => $musicState->youtube_url,
            'playback_status' => $musicState->playback_status->value,
            'position_seconds' => $musicState->position_seconds,
            'started_at' => $musicState->started_at?->toISOString(),
            'updated_at' => $musicState->updated_at?->toISOString(),
        ];
    }
}
