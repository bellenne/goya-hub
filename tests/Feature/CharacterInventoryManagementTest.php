<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Enums\SessionStatus;
use App\Events\SessionCharacterInventoryUpdated;
use App\Models\Character;
use App\Models\CharacterInventoryItem;
use App\Models\Game;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CharacterInventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_gm_can_issue_catalog_and_custom_items_and_player_sees_them(): void
    {
        Storage::fake('public');

        [$game, $gm, $player, $character] = $this->createGameWithCharacter();

        $catalogItem = Item::query()->create([
            'game_id' => $game->id,
            'name' => 'Signal Knife',
            'category' => 'Weapon',
            'description' => 'A thin blade for emergencies.',
        ]);

        $this->actingAs($gm)->post(route('games.characters.inventory.store', [$game, $character]), [
            'item_id' => $catalogItem->id,
            'quantity' => 2,
        ])->assertRedirect(route('games.characters.show', [$game, $character], absolute: false));

        $this->actingAs($gm)->post(route('games.characters.inventory.store', [$game, $character]), [
            'custom_name' => 'Field Ration',
            'custom_description' => 'Keeps for two nights.',
            'custom_image' => UploadedFile::fake()->createWithContent('ration.png', $this->tinyPng()),
            'quantity' => 3,
        ])->assertRedirect(route('games.characters.show', [$game, $character], absolute: false));

        $this->assertDatabaseHas('character_inventory_items', [
            'character_id' => $character->id,
            'item_id' => $catalogItem->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('character_inventory_items', [
            'character_id' => $character->id,
            'custom_name' => 'Field Ration',
            'quantity' => 3,
        ]);

        $this->actingAs($player)->get(route('games.characters.show', [$game, $character]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Characters/Show')
                ->where('character.inventory_items.0.name', 'Signal Knife')
                ->where('character.inventory_items.1.name', 'Field Ration')
                ->where('can_manage_inventory', false)
            );
    }

    public function test_gm_can_update_quantity_and_delete_inventory_item(): void
    {
        [$game, $gm, $player, $character] = $this->createGameWithCharacter();

        $inventoryItem = CharacterInventoryItem::query()->create([
            'character_id' => $character->id,
            'custom_name' => 'Torch',
            'custom_description' => 'Still dry.',
            'quantity' => 1,
        ]);

        $this->actingAs($gm)->patch(route('games.characters.inventory.update', [$game, $character, $inventoryItem]), [
            'quantity' => 5,
        ])->assertRedirect(route('games.characters.show', [$game, $character], absolute: false));

        $this->assertDatabaseHas('character_inventory_items', [
            'id' => $inventoryItem->id,
            'quantity' => 5,
        ]);

        $this->actingAs($gm)->delete(route('games.characters.inventory.destroy', [$game, $character, $inventoryItem]))
            ->assertRedirect(route('games.characters.show', [$game, $character], absolute: false));

        $this->assertDatabaseMissing('character_inventory_items', [
            'id' => $inventoryItem->id,
        ]);
    }

    public function test_inventory_changes_are_broadcast_and_visible_in_active_session(): void
    {
        Event::fake([SessionCharacterInventoryUpdated::class]);
        Storage::fake('public');

        [$game, $gm, $player, $character] = $this->createGameWithCharacter();

        $session = $game->sessions()->create([
            'title' => 'Inventory Live',
            'invite_code' => 'INV123',
            'invite_token' => str_repeat('d', 64),
            'status' => SessionStatus::Active,
        ]);

        $session->participants()->create([
            'user_id' => $gm->id,
            'joined_at' => now()->subMinute(),
        ]);

        $session->participants()->create([
            'user_id' => $player->id,
            'joined_at' => now(),
        ]);

        $catalogItem = Item::query()->create([
            'game_id' => $game->id,
            'name' => 'Storm Lantern',
            'category' => 'Tool',
            'description' => 'Burns through the fog.',
        ]);

        $this->actingAs($gm)->post(route('games.characters.inventory.store', [$game, $character]), [
            'item_id' => $catalogItem->id,
            'quantity' => 2,
            'back_to_session_id' => $session->id,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $inventoryItem = CharacterInventoryItem::query()->firstOrFail();

        Event::assertDispatched(SessionCharacterInventoryUpdated::class);

        $this->actingAs($player)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('inventory.own_character.inventory_items.0.name', 'Storm Lantern')
                ->where('inventory.own_character.inventory_items.0.quantity', 2)
            );

        $this->actingAs($gm)->patch(route('games.characters.inventory.update', [$game, $character, $inventoryItem]), [
            'quantity' => 5,
            'back_to_session_id' => $session->id,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->actingAs($player)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('inventory.own_character.inventory_items.0.quantity', 5)
            );

        $this->actingAs($gm)->delete(route('games.characters.inventory.destroy', [$game, $character, $inventoryItem]), [
            'back_to_session_id' => $session->id,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->actingAs($player)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('inventory.own_character.inventory_items', [])
            );
    }

    protected function createGameWithCharacter(): array
    {
        $gm = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($gm)->post(route('games.store'), [
            'name' => 'Inventory Table',
            'description' => 'Inventory flow',
        ]);

        /** @var Game $game */
        $game = Game::query()->firstOrFail();
        $game->members()->create([
            'user_id' => $player->id,
            'role' => GameRole::Player,
        ]);

        $character = Character::query()->create([
            'game_id' => $game->id,
            'user_id' => $player->id,
            'name' => 'Lio Varn',
            'origin' => 'South Reach',
            'notes' => 'Carries maps.',
            'attribute_values' => ['strength' => 2, 'agility' => 3, 'mind' => 3],
            'skill_values' => ['observation' => 2, 'stealth' => 1, 'persuasion' => 1, 'tracking' => 0],
            'extra_field_values' => ['calling' => 'Scout', 'luck' => 1, 'motto' => 'Travel light.'],
            'is_active' => true,
        ]);

        return [$game, $gm, $player, $character];
    }

    protected function tinyPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sZ6uWAAAAAASUVORK5CYII=');
    }
}

