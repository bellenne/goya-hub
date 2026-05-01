<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Events\SessionDiceRolled;
use App\Http\Requests\StoreSessionDiceRollRequest;
use App\Models\Game;
use App\Models\GameSession;
use App\Services\Sessions\RollSessionDice;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SessionDiceRollController extends Controller
{
    public function store(
        StoreSessionDiceRollRequest $request,
        Game $game,
        GameSession $session,
        RollSessionDice $rollSessionDice,
    ): RedirectResponse {
        abort_unless($session->game_id === $game->id, 404);
        Gate::authorize('view', $session);

        abort_if($session->status !== SessionStatus::Active, Response::HTTP_CONFLICT);

        try {
            $roll = $rollSessionDice->handle($session, $request->user(), $request->validated());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'dice_type' => $exception->getMessage(),
            ]);
        }

        broadcast(new SessionDiceRolled($roll->load('user')))->toOthers();

        return redirect()
            ->route('games.sessions.show', [$game, $session])
            ->with('success', 'Roll saved.');
    }
}
