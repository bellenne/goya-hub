<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCharacterTemplateRequest;
use App\Models\Game;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameCharacterTemplateController extends Controller
{
    public function edit(Game $game): Response
    {
        Gate::authorize('manageContent', $game);

        return Inertia::render('Games/CharacterTemplate/Edit', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'template' => $game->resolvedCharacterTemplate(),
        ]);
    }

    public function update(UpdateCharacterTemplateRequest $request, Game $game): RedirectResponse
    {
        Gate::authorize('manageContent', $game);

        $game->update([
            'character_template' => $request->template(),
        ]);

        return redirect()
            ->route('games.character-template.edit', $game)
            ->with('success', 'Шаблон листа персонажа сохранён.');
    }
}
