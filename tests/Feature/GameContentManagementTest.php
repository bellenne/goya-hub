<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_gm_can_create_npc_item_and_background(): void
    {
        Storage::fake('public');

        [$game, $gm] = $this->createGameForContent();

        $npcAvatar = UploadedFile::fake()->createWithContent('npc.png', $this->tinyPng());
        $itemImage = UploadedFile::fake()->createWithContent('item.png', $this->tinyPng());
        $backgroundImage = UploadedFile::fake()->createWithContent('background.png', $this->tinyPng());

        $this->actingAs($gm)->post(route('games.npcs.store', $game), [
            'name' => 'Watcher',
            'type' => 'ally',
            'description' => 'Keeps the gate.',
            'stats' => '{"power":2}',
            'avatar' => $npcAvatar,
        ])->assertRedirect(route('games.npcs.index', $game, absolute: false));

        $this->actingAs($gm)->post(route('games.items.store', $game), [
            'name' => 'Lantern Key',
            'category' => 'Quest',
            'description' => 'Opens old signal lanterns.',
            'image' => $itemImage,
        ])->assertRedirect(route('games.items.index', $game, absolute: false));

        $this->actingAs($gm)->post(route('games.backgrounds.store', $game), [
            'title' => 'Ruined Gate',
            'image' => $backgroundImage,
        ])->assertRedirect(route('games.backgrounds.index', $game, absolute: false));

        $this->assertDatabaseHas('npcs', ['game_id' => $game->id, 'name' => 'Watcher', 'type' => 'ally']);
        $this->assertDatabaseHas('items', ['game_id' => $game->id, 'name' => 'Lantern Key']);
        $this->assertDatabaseHas('backgrounds', ['game_id' => $game->id, 'title' => 'Ruined Gate']);

        $this->actingAs($gm)->get(route('games.npcs.index', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Content/Npcs')
                ->where('npcs.0.name', 'Watcher')
            );

        $this->actingAs($gm)->get(route('games.items.index', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Content/Items')
                ->where('items.0.name', 'Lantern Key')
            );

        $this->actingAs($gm)->get(route('games.backgrounds.index', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Content/Backgrounds')
                ->where('backgrounds.0.title', 'Ruined Gate')
            );
    }

    public function test_player_cannot_access_content_management_sections(): void
    {
        [$game, $gm, $player] = $this->createGameForContent(withPlayer: true);

        $this->actingAs($player)->get(route('games.npcs.index', $game))->assertForbidden();
        $this->actingAs($player)->get(route('games.items.index', $game))->assertForbidden();
        $this->actingAs($player)->get(route('games.backgrounds.index', $game))->assertForbidden();

        $this->actingAs($player)->post(route('games.npcs.store', $game), [
            'name' => 'Blocked NPC',
            'type' => 'neutral',
        ])->assertForbidden();
    }

    protected function createGameForContent(bool $withPlayer = false): array
    {
        $gm = User::factory()->create();
        $this->actingAs($gm)->post(route('games.store'), [
            'name' => 'Content Table',
            'description' => 'Content management',
        ]);

        /** @var Game $game */
        $game = Game::query()->firstOrFail();

        if (! $withPlayer) {
            return [$game, $gm];
        }

        $player = User::factory()->create();
        $game->members()->create([
            'user_id' => $player->id,
            'role' => GameRole::Player,
        ]);

        return [$game, $gm, $player];
    }

    protected function tinyPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sZ6uWAAAAAASUVORK5CYII=');
    }
}
