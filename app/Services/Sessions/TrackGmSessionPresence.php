<?php

namespace App\Services\Sessions;

use App\Enums\SessionStatus;
use App\Events\SessionLifecycleUpdated;
use App\Jobs\DetectGmSessionPresenceLost;
use App\Jobs\EndGameSessionAfterGmGrace;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackGmSessionPresence
{
    public const HEARTBEAT_INTERVAL_SECONDS = 30;
    public const HEARTBEAT_STALE_AFTER_SECONDS = 150;
    public const GRACE_PERIOD_SECONDS = 300;

    public function connect(GameSession $session, User $gm, string $connectionId): GameSession
    {
        $event = null;

        $session = DB::transaction(function () use ($session, $gm, $connectionId, &$event) {
            $locked = GameSession::query()->lockForUpdate()->findOrFail($session->id);

            abort_if($locked->status === SessionStatus::Ended, Response::HTTP_CONFLICT, 'Session has ended.');

            $locked->gmConnections()->updateOrCreate(
                ['connection_id' => $connectionId],
                [
                    'user_id' => $gm->id,
                    'connected_at' => now(),
                    'last_seen_at' => now(),
                    'disconnected_at' => null,
                ],
            );

            if ($locked->status === SessionStatus::GmDisconnectedGrace) {
                $locked->update([
                    'status' => SessionStatus::tryFrom((string) $locked->status_before_gm_disconnect) ?? SessionStatus::Active,
                    'status_before_gm_disconnect' => null,
                    'gm_grace_started_at' => null,
                    'gm_grace_ends_at' => null,
                ]);
                $event = 'gm_returned';
            }

            return $locked->fresh();
        });

        $this->dispatchStaleDetection($session);

        if ($event !== null) {
            broadcast(new SessionLifecycleUpdated($session, $event))->toOthers();
        }

        return $session;
    }

    public function heartbeat(GameSession $session, User $gm, string $connectionId): GameSession
    {
        $session = $this->connect($session, $gm, $connectionId);
        $this->dispatchStaleDetection($session);

        return $session;
    }

    public function disconnect(GameSession $session, string $connectionId): void
    {
        $session->gmConnections()
            ->where('connection_id', $connectionId)
            ->whereNull('disconnected_at')
            ->update(['disconnected_at' => now()]);

        $this->startGraceIfNoLiveGm($session);
    }

    public function startGraceIfNoLiveGm(GameSession $session): void
    {
        $updated = null;

        DB::transaction(function () use ($session, &$updated) {
            $locked = GameSession::query()->lockForUpdate()->findOrFail($session->id);

            if (
                $locked->status === SessionStatus::Ended
                || $locked->status === SessionStatus::GmDisconnectedGrace
                || $this->hasLiveGmConnection($locked)
            ) {
                return;
            }

            $locked->update([
                'status_before_gm_disconnect' => $locked->status->value,
                'status' => SessionStatus::GmDisconnectedGrace,
                'gm_grace_started_at' => now(),
                'gm_grace_ends_at' => now()->addSeconds(self::GRACE_PERIOD_SECONDS),
            ]);

            $updated = $locked->fresh();
        });

        if ($updated === null) {
            return;
        }

        $this->dispatchGraceEnd($updated);

        broadcast(new SessionLifecycleUpdated($updated, 'gm_disconnected'))->toOthers();
    }

    public function endGraceIfExpired(GameSession $session): void
    {
        $event = null;
        $updated = null;

        DB::transaction(function () use ($session, &$event, &$updated) {
            $locked = GameSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($locked->status !== SessionStatus::GmDisconnectedGrace) {
                return;
            }

            if ($this->hasLiveGmConnection($locked)) {
                $locked->update([
                    'status' => SessionStatus::tryFrom((string) $locked->status_before_gm_disconnect) ?? SessionStatus::Active,
                    'status_before_gm_disconnect' => null,
                    'gm_grace_started_at' => null,
                    'gm_grace_ends_at' => null,
                ]);

                $event = 'gm_returned';
                $updated = $locked->fresh();

                return;
            }

            if ($locked->gm_grace_ends_at !== null && $locked->gm_grace_ends_at->isFuture()) {
                $this->dispatchGraceEnd($locked);

                return;
            }

            $locked->update([
                'status' => SessionStatus::Ended,
                'ended_at' => now(),
                'status_before_gm_disconnect' => null,
                'gm_grace_started_at' => null,
                'gm_grace_ends_at' => null,
            ]);

            $event = 'ended';
            $updated = $locked->fresh();
        });

        if ($event !== null && $updated !== null) {
            broadcast(new SessionLifecycleUpdated($updated, $event))->toOthers();
        }
    }

    public function hasLiveGmConnection(GameSession $session): bool
    {
        return $session->gmConnections()
            ->whereNull('disconnected_at')
            ->where('last_seen_at', '>=', now()->subSeconds(self::HEARTBEAT_STALE_AFTER_SECONDS))
            ->exists();
    }

    protected function dispatchStaleDetection(GameSession $session): void
    {
        DetectGmSessionPresenceLost::dispatch($session->id)
            ->delay(now()->addSeconds(self::HEARTBEAT_STALE_AFTER_SECONDS + 5));
    }

    protected function dispatchGraceEnd(GameSession $session): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        EndGameSessionAfterGmGrace::dispatch($session->id)
            ->delay($session->gm_grace_ends_at->copy()->addSecond());
    }
}
