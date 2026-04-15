<?php

namespace App\Http\Controllers;

use App\Concerns\StoresPublicUploads;
use App\Http\Requests\UpsertBackgroundRequest;
use App\Models\Background;
use App\Models\Game;
use App\Models\GameSession;
use App\Services\Sessions\UpdateSessionScene;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameBackgroundController extends Controller
{
    use StoresPublicUploads;

    public function index(Game $game): Response
    {
        Gate::authorize('manageContent', $game);

        return Inertia::render('Games/Content/Backgrounds', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'backgrounds' => $game->backgrounds()
                ->latest()
                ->get()
                ->map(fn (Background $background) => [
                    'id' => $background->id,
                    'title' => $background->title,
                    'image_url' => $background->image_path ? Storage::disk('public')->url($background->image_path) : null,
                ]),
        ]);
    }

    public function store(UpsertBackgroundRequest $request, Game $game, UpdateSessionScene $updateSessionScene): RedirectResponse
    {
        Gate::authorize('manageContent', $game);

        $validated = $request->validated();

        $background = $game->backgrounds()->create([
            'title' => $validated['title'],
            'image_path' => $this->storePublicUpload($validated['image'] ?? null, 'backgrounds'),
        ]);

        $session = isset($validated['back_to_session_id'])
            ? $game->sessions()->find($validated['back_to_session_id'])
            : null;

        if ($session instanceof GameSession) {
            if (($validated['apply_to_session'] ?? false) === true) {
                $updateSessionScene->handle($session, ['background_id' => $background->id]);
            }

            return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'Background created.');
        }

        return redirect()->route('games.backgrounds.index', $game)->with('success', 'Background created.');
    }

    public function edit(Game $game, Background $background): Response
    {
        Gate::authorize('manageContent', $game);
        abort_unless($background->game_id === $game->id, 404);

        return Inertia::render('Games/Content/EditBackground', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'background' => [
                'id' => $background->id,
                'title' => $background->title,
                'image_url' => $background->image_path ? Storage::disk('public')->url($background->image_path) : null,
            ],
        ]);
    }

    public function update(UpsertBackgroundRequest $request, Game $game, Background $background): RedirectResponse
    {
        Gate::authorize('manageContent', $game);
        abort_unless($background->game_id === $game->id, 404);

        $validated = $request->validated();

        $background->update([
            'title' => $validated['title'],
            'image_path' => $this->storePublicUpload($validated['image'] ?? null, 'backgrounds', $background->image_path),
        ]);

        return redirect()->route('games.backgrounds.index', $game)->with('success', 'Background updated.');
    }
}
