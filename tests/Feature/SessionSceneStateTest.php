<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Enums\NpcType;
use App\Enums\SessionStatus;
use App\Events\SessionSceneUpdated;
use App\Models\Background;
use App\Models\Character;
use App\Models\Game;
use App\Models\Npc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SessionSceneStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_gm_can_update_scene_and_player_receives_active_table_payload(): void
    {
        Event::fake([SessionSceneUpdated::class]);

        [$game, $gm, $player, $session, $character] = $this->createActiveSession();

        $background = Background::query()->create([
            'game_id' => $game->id,
            'title' => 'Moon Gate',
            'image_path' => 'backgrounds/moon-gate.png',
        ]);

        $npc = Npc::query()->create([
            'game_id' => $game->id,
            'name' => 'Ash Herald',
            'type' => NpcType::Neutral,
            'description' => 'Messenger in grey ash.',
        ]);

        $response = $this->actingAs($gm)->patch(route('games.sessions.scene.update', [$game, $session]), [
            'background_id' => $background->id,
            'visible_npc_ids' => [$npc->id],
            'speaker_type' => 'npc',
            'speaker_id' => $npc->id,
        ]);

        $response->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->actingAs($player)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('scene.background.title', 'Moon Gate')
                ->where('scene.visible_npcs.0.name', 'Ash Herald')
                ->where('scene.speaker.type', 'npc')
                ->where('scene.speaker.name', 'Ash Herald')
                ->where('scene.own_character.name', $character->name)
                ->where('can_manage_sessions', false)
            );

        Event::assertDispatched(SessionSceneUpdated::class);
    }

    public function test_player_cannot_manage_scene(): void
    {
        [$game, $gm, $player, $session] = $this->createActiveSession();

        $response = $this->actingAs($player)->patch(route('games.sessions.scene.update', [$game, $session]), [
            'visible_npc_ids' => [],
        ]);

        $response->assertForbidden();
    }

    public function test_gm_can_hide_npc_and_switch_speaker_to_character(): void
    {
        [$game, $gm, $player, $session, $character] = $this->createActiveSession();

        $npc = Npc::query()->create([
            'game_id' => $game->id,
            'name' => 'Crow Broker',
            'type' => NpcType::Ally,
            'description' => 'Knows everyone.',
        ]);

        $this->actingAs($gm)->patch(route('games.sessions.scene.update', [$game, $session]), [
            'visible_npc_ids' => [$npc->id],
            'speaker_type' => 'npc',
            'speaker_id' => $npc->id,
        ]);

        $this->actingAs($gm)->patch(route('games.sessions.scene.update', [$game, $session]), [
            'visible_npc_ids' => [],
            'speaker_type' => 'character',
            'speaker_id' => $character->id,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->actingAs($player)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->has('scene.visible_npcs', 0)
                ->where('scene.speaker.type', 'character')
                ->where('scene.speaker.id', $character->id)
            );
    }

    public function test_gm_can_separate_encountered_present_and_ally_npc_scene_states(): void
    {
        [$game, $gm, $player, $session] = $this->createActiveSession();

        $metNpc = Npc::query()->create([
            'game_id' => $game->id,
            'name' => 'Archivist Veil',
            'type' => NpcType::Neutral,
            'description' => 'Keeps old names.',
            'stats' => ['mind' => 4],
        ]);

        $allyNpc = Npc::query()->create([
            'game_id' => $game->id,
            'name' => 'Torchbearer Lio',
            'type' => NpcType::Ally,
            'description' => 'Carries the spare lantern.',
        ]);

        $hiddenNpc = Npc::query()->create([
            'game_id' => $game->id,
            'name' => 'Unseen Regent',
            'type' => NpcType::Enemy,
            'description' => 'Not revealed yet.',
        ]);

        $this->actingAs($gm)->patch(route('games.sessions.scene.update', [$game, $session]), [
            'encountered_npc_ids' => [$metNpc->id, $allyNpc->id],
            'present_npc_ids' => [$allyNpc->id],
            'speaker_type' => 'npc',
            'speaker_id' => $allyNpc->id,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->assertDatabaseHas('session_scene_state_npcs', [
            'npc_id' => $metNpc->id,
            'is_encountered' => true,
            'is_present' => false,
        ]);
        $this->assertDatabaseHas('session_scene_state_npcs', [
            'npc_id' => $allyNpc->id,
            'is_encountered' => true,
            'is_present' => true,
        ]);
        $this->assertDatabaseMissing('session_scene_state_npcs', [
            'npc_id' => $hiddenNpc->id,
        ]);

        $this->actingAs($player)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('scene.encountered_npcs.0.name', 'Archivist Veil')
                ->where('scene.encountered_npcs.0.is_present', false)
                ->where('scene.encountered_npcs.0.has_stats', true)
                ->where('scene.encountered_npcs.1.name', 'Torchbearer Lio')
                ->where('scene.present_npcs.0.name', 'Torchbearer Lio')
                ->where('scene.present_npcs.0.type', 'ally')
                ->where('scene.speaker.id', $allyNpc->id)
                ->where('scene.controls.npcs.0.is_encountered', true)
                ->where('scene.controls.npcs.0.is_present', false)
                ->where('scene.controls.npcs.1.is_present', true)
            );
    }

    public function test_gm_can_add_multiple_separate_scene_npcs_and_group_npc(): void
    {
        [$game, $gm, $player, $session] = $this->createActiveSession();

        $npc = Npc::query()->create([
            'game_id' => $game->id,
            'name' => 'Goblin',
            'type' => NpcType::Enemy,
            'description' => 'Small and loud.',
        ]);

        $this->actingAs($gm)->post(route('games.sessions.scene-npcs.store', [$game, $session]), [
            'npc_id' => $npc->id,
            'scene_type' => 'enemy',
            'quantity' => 5,
            'create_group' => false,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->assertDatabaseCount('session_scene_state_npcs', 5);
        $this->assertDatabaseMissing('session_scene_state_npcs', [
            'npc_id' => $npc->id,
            'is_group' => true,
        ]);

        $this->actingAs($gm)->post(route('games.sessions.scene-npcs.store', [$game, $session]), [
            'npc_id' => $npc->id,
            'scene_type' => 'enemy',
            'quantity' => 5,
            'create_group' => true,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->assertDatabaseHas('session_scene_state_npcs', [
            'npc_id' => $npc->id,
            'display_name' => 'Группа Goblin',
            'is_group' => true,
            'group_size' => 5,
        ]);

        $this->actingAs($player)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('scene.present_npcs.0.name', 'Goblin')
                ->where('scene.present_npcs.5.name', 'Группа Goblin')
                ->where('scene.present_npcs.5.is_group', true)
                ->where('scene.present_npcs.5.group_size', 5)
            );
    }

    public function test_gm_can_update_character_sheet_from_session(): void
    {
        [$game, $gm, $player, $session, $character] = $this->createActiveSession();

        $this->actingAs($gm)->patch(route('games.characters.sheet.update', [$game, $character]), [
            'attribute_values' => ['strength' => 4, 'agility' => 2, 'mind' => 5],
            'skill_values' => ['observation' => 3, 'stealth' => 2],
            'extra_field_values' => ['calling' => 'Scout', 'luck' => 2],
            'back_to_session_id' => $session->id,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
        ]);

        $character->refresh();

        $this->assertSame(4, $character->attribute_values['strength']);
        $this->assertSame(3, $character->skill_values['observation']);
        $this->assertSame('Scout', $character->extra_field_values['calling']);
    }

    protected function createActiveSession(): array
    {
        $gm = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($gm)->post(route('games.store'), [
            'name' => 'Scene Table',
            'description' => 'Scene state',
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
            'name' => 'Mila Kest',
            'origin' => 'Grey Harbor',
            'notes' => 'Watches the skyline.',
            'attribute_values' => ['strength' => 2, 'agility' => 3, 'mind' => 3],
            'skill_values' => ['observation' => 2, 'stealth' => 1, 'persuasion' => 1, 'tracking' => 0],
            'extra_field_values' => ['calling' => 'Lookout', 'luck' => 1, 'motto' => 'First to notice.'],
            'is_active' => true,
        ]);

        $session = $game->sessions()->create([
            'title' => 'Act One',
            'invite_code' => 'ZXCV12',
            'invite_token' => str_repeat('b', 64),
            'status' => SessionStatus::Active,
        ]);

        $session->participants()->create([
            'user_id' => $player->id,
            'joined_at' => now(),
        ]);

        return [$game, $gm, $player, $session, $character];
    }
}

