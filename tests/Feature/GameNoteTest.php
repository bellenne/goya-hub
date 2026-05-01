<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Models\Game;
use App\Models\GameNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_personal_game_notes(): void
    {
        [$game, $gm, $player] = $this->createGameWithPlayer();

        $createResponse = $this->actingAs($player)->postJson(route('games.notes.store', $game), [
            'title' => 'Session clues',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('note.title', 'Session clues');

        $noteId = $createResponse->json('note.id');

        $this->actingAs($player)->patchJson(route('games.notes.update', [$game, $noteId]), [
            'title' => 'Session clues',
            'content' => 'The lock has a moon mark.',
        ])->assertOk()
            ->assertJsonPath('note.content', 'The lock has a moon mark.');

        $this->actingAs($player)->getJson(route('games.notes.index', $game))
            ->assertOk()
            ->assertJsonCount(1, 'notes')
            ->assertJsonPath('notes.0.id', $noteId);

        $this->actingAs($gm)->getJson(route('games.notes.index', $game))
            ->assertOk()
            ->assertJsonCount(0, 'notes');
    }

    public function test_user_can_attach_and_delete_note_image(): void
    {
        Storage::fake('public');

        [$game, , $player] = $this->createGameWithPlayer();
        $note = GameNote::query()->create([
            'game_id' => $game->id,
            'user_id' => $player->id,
            'title' => 'Map',
            'content' => '',
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        $response = $this->actingAs($player)->postJson(route('games.notes.attachments.store', [$game, $note]), [
            'image' => UploadedFile::fake()->createWithContent('map.png', $png),
        ]);

        $response->assertCreated()
            ->assertJsonCount(1, 'note.attachments');

        $attachmentId = $response->json('attachment.id');

        $this->actingAs($player)->deleteJson(route('games.notes.attachments.destroy', [$game, $note, $attachmentId]))
            ->assertOk()
            ->assertJsonCount(0, 'note.attachments');
    }

    protected function createGameWithPlayer(): array
    {
        $gm = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($gm)->post(route('games.store'), [
            'name' => 'Notes Game',
            'description' => 'Notes tests',
        ]);

        $game = Game::query()->firstOrFail();
        $game->members()->create([
            'user_id' => $player->id,
            'role' => GameRole::Player,
        ]);

        return [$game, $gm, $player];
    }
}
