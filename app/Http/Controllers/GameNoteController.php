<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameNote;
use App\Models\GameNoteAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GameNoteController extends Controller
{
    public function index(Request $request, Game $game): JsonResponse
    {
        Gate::authorize('viewSessions', $game);

        $notes = $game->notes()
            ->with('attachments')
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->get()
            ->map(fn (GameNote $note) => $this->notePayload($note));

        return response()->json(['notes' => $notes]);
    }

    public function store(Request $request, Game $game): JsonResponse
    {
        Gate::authorize('viewSessions', $game);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
        ]);

        $note = $game->notes()->create([
            'user_id' => $request->user()->id,
            'title' => trim($validated['title'] ?? '') ?: 'Новая заметка',
            'content' => '',
        ]);

        return response()->json(['note' => $this->notePayload($note->load('attachments'))], 201);
    }

    public function update(Request $request, Game $game, GameNote $note): JsonResponse
    {
        $this->authorizeNote($request, $game, $note);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['nullable', 'string', 'max:100000'],
            'sketch_data' => ['nullable', 'string'],
            'clear_sketch' => ['nullable', 'boolean'],
        ]);

        $updates = [
            'title' => trim($validated['title']) ?: 'Без названия',
            'content' => $validated['content'] ?? '',
        ];

        if ($request->boolean('clear_sketch')) {
            if ($note->sketch_path) {
                Storage::disk('public')->delete($note->sketch_path);
            }

            $updates['sketch_path'] = null;
        } elseif (! empty($validated['sketch_data'])) {
            $updates['sketch_path'] = $this->storeSketch($validated['sketch_data'], $note->sketch_path);
        }

        $note->update($updates);

        return response()->json(['note' => $this->notePayload($note->fresh('attachments'))]);
    }

    public function destroy(Request $request, Game $game, GameNote $note): JsonResponse
    {
        $this->authorizeNote($request, $game, $note);

        if ($note->sketch_path) {
            Storage::disk('public')->delete($note->sketch_path);
        }

        $note->attachments->each(fn (GameNoteAttachment $attachment) => Storage::disk('public')->delete($attachment->image_path));
        $note->delete();

        return response()->json(['ok' => true]);
    }

    public function storeAttachment(Request $request, Game $game, GameNote $note): JsonResponse
    {
        $this->authorizeNote($request, $game, $note);

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $file = $validated['image'];
        $attachment = $note->attachments()->create([
            'image_path' => $file->store('game-notes/attachments', 'public'),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        $note->touch();

        return response()->json([
            'attachment' => $this->attachmentPayload($attachment),
            'note' => $this->notePayload($note->fresh('attachments')),
        ], 201);
    }

    public function destroyAttachment(Request $request, Game $game, GameNote $note, GameNoteAttachment $attachment): JsonResponse
    {
        $this->authorizeNote($request, $game, $note);
        abort_unless($attachment->game_note_id === $note->id, 404);

        Storage::disk('public')->delete($attachment->image_path);
        $attachment->delete();
        $note->touch();

        return response()->json(['note' => $this->notePayload($note->fresh('attachments'))]);
    }

    protected function authorizeNote(Request $request, Game $game, GameNote $note): void
    {
        Gate::authorize('viewSessions', $game);
        abort_unless($note->game_id === $game->id && $note->user_id === $request->user()->id, 404);
    }

    protected function storeSketch(string $dataUrl, ?string $currentPath = null): string
    {
        abort_unless(str_starts_with($dataUrl, 'data:image/png;base64,'), 422);

        $encoded = substr($dataUrl, strlen('data:image/png;base64,'));
        $binary = base64_decode($encoded, true);
        abort_if($binary === false || strlen($binary) > 2_500_000, 422);

        $path = 'game-notes/sketches/'.Str::uuid().'.png';
        Storage::disk('public')->put($path, $binary);

        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        return $path;
    }

    protected function notePayload(GameNote $note): array
    {
        return [
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content ?? '',
            'sketch_url' => $note->sketch_path ? Storage::disk('public')->url($note->sketch_path) : null,
            'attachments' => $note->attachments
                ->sortByDesc('created_at')
                ->values()
                ->map(fn (GameNoteAttachment $attachment) => $this->attachmentPayload($attachment)),
            'updated_at' => $note->updated_at?->toISOString(),
        ];
    }

    protected function attachmentPayload(GameNoteAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'size' => $attachment->size,
            'url' => Storage::disk('public')->url($attachment->image_path),
        ];
    }
}
