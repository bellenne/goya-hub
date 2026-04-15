<?php

namespace App\Http\Controllers;

use App\Concerns\StoresPublicUploads;
use App\Http\Requests\UpsertItemRequest;
use App\Models\Game;
use App\Models\Item;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameItemController extends Controller
{
    use StoresPublicUploads;

    public function index(Game $game): Response
    {
        Gate::authorize('manageContent', $game);

        return $this->renderIndex($game);
    }

    public function store(UpsertItemRequest $request, Game $game): RedirectResponse
    {
        Gate::authorize('manageContent', $game);

        $validated = $request->validated();

        $game->items()->create([
            'name' => $validated['name'],
            'image_path' => $this->storePublicUpload($validated['image'] ?? null, 'items'),
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('games.items.index', $game)->with('success', 'Предмет создан.');
    }

    public function edit(Game $game, Item $item): Response
    {
        Gate::authorize('manageContent', $game);
        abort_unless($item->game_id === $game->id, 404);

        return $this->renderIndex($game, $item);
    }

    public function update(UpsertItemRequest $request, Game $game, Item $item): RedirectResponse
    {
        Gate::authorize('manageContent', $game);
        abort_unless($item->game_id === $game->id, 404);

        $validated = $request->validated();

        $item->update([
            'name' => $validated['name'],
            'image_path' => $this->storePublicUpload($validated['image'] ?? null, 'items', $item->image_path),
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('games.items.edit', [$game, $item])->with('success', 'Предмет сохранён.');
    }

    public function destroy(Game $game, Item $item): RedirectResponse
    {
        Gate::authorize('manageContent', $game);
        abort_unless($item->game_id === $game->id, 404);

        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        return redirect()->route('games.items.index', $game)->with('success', 'Предмет удалён.');
    }

    protected function renderIndex(Game $game, ?Item $selectedItem = null): Response
    {
        return Inertia::render('Games/Content/Items', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'items' => $game->items()
                ->latest()
                ->get()
                ->map(fn (Item $item) => $this->itemPayload($item)),
            'selectedItem' => $selectedItem ? $this->itemPayload($selectedItem) : null,
        ]);
    }

    protected function itemPayload(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'description' => $item->description,
            'image_url' => $item->image_path ? Storage::disk('public')->url($item->image_path) : null,
        ];
    }
}
