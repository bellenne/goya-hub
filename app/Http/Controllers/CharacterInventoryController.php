<?php

namespace App\Http\Controllers;

use App\Concerns\StoresPublicUploads;
use App\Http\Requests\StoreCharacterInventoryItemRequest;
use App\Http\Requests\UpdateCharacterInventoryItemRequest;
use App\Models\Character;
use App\Models\CharacterInventoryItem;
use App\Models\Game;
use App\Services\Sessions\BroadcastCharacterInventoryUpdate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CharacterInventoryController extends Controller
{
    use StoresPublicUploads;

    public function store(
        StoreCharacterInventoryItemRequest $request,
        Game $game,
        Character $character,
        BroadcastCharacterInventoryUpdate $broadcastCharacterInventoryUpdate,
    ): RedirectResponse {
        abort_unless($character->game_id === $game->id, 404);
        Gate::authorize('manageInventory', $character);

        $validated = $request->validated();
        $itemId = $validated['item_id'] ?? null;

        $character->inventoryItems()->create([
            'item_id' => $itemId,
            'custom_name' => $itemId ? null : ($validated['custom_name'] ?? null),
            'custom_description' => $itemId ? null : ($validated['custom_description'] ?? null),
            'custom_image_path' => $itemId ? null : $this->storePublicUpload($validated['custom_image'] ?? null, 'inventory-items'),
            'quantity' => $validated['quantity'],
        ]);

        $broadcastCharacterInventoryUpdate->handle($character->fresh(['game.sessions']));

        return $this->redirectAfterChange($request, $game, $character, 'Inventory item added.');
    }

    public function update(
        UpdateCharacterInventoryItemRequest $request,
        Game $game,
        Character $character,
        CharacterInventoryItem $inventoryItem,
        BroadcastCharacterInventoryUpdate $broadcastCharacterInventoryUpdate,
    ): RedirectResponse {
        abort_unless($character->game_id === $game->id && $inventoryItem->character_id === $character->id, 404);
        Gate::authorize('manageInventory', $character);

        $inventoryItem->update([
            'quantity' => $request->validated()['quantity'],
        ]);

        $broadcastCharacterInventoryUpdate->handle($character->fresh(['game.sessions']));

        return $this->redirectAfterChange($request, $game, $character, 'Inventory quantity updated.');
    }

    public function destroy(
        Game $game,
        Character $character,
        CharacterInventoryItem $inventoryItem,
        BroadcastCharacterInventoryUpdate $broadcastCharacterInventoryUpdate,
    ): RedirectResponse {
        abort_unless($character->game_id === $game->id && $inventoryItem->character_id === $character->id, 404);
        Gate::authorize('manageInventory', $character);

        if ($inventoryItem->custom_image_path) {
            Storage::disk('public')->delete($inventoryItem->custom_image_path);
        }

        $inventoryItem->delete();

        $broadcastCharacterInventoryUpdate->handle($character->fresh(['game.sessions']));

        return $this->redirectAfterChange(request(), $game, $character, 'Inventory item removed.');
    }

    protected function redirectAfterChange($request, Game $game, Character $character, string $message): RedirectResponse
    {
        $sessionId = $request->input('back_to_session_id');

        if ($sessionId) {
            $session = $game->sessions()->find($sessionId);

            if ($session !== null) {
                return redirect()->route('games.sessions.show', [$game, $session])->with('success', $message);
            }
        }

        return redirect()->route('games.characters.show', [$game, $character])->with('success', $message);
    }
}
