<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Models\GameInvite;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_game_and_becomes_gm(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('games.store'), [
            'name' => 'Curse of the Amber Table',
            'description' => 'MVP campaign',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('games', [
            'name' => 'Curse of the Amber Table',
            'owner_id' => $user->id,
        ]);

        $this->assertDatabaseHas('game_members', [
            'user_id' => $user->id,
            'role' => GameRole::Gm->value,
        ]);
    }

    public function test_user_sees_only_own_games_in_list(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)->post(route('games.store'), [
            'name' => 'First Table',
            'description' => null,
        ]);

        $this->actingAs($otherUser)->post(route('games.store'), [
            'name' => 'Other Table',
            'description' => null,
        ]);

        $response = $this->actingAs($user)->get(route('games.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Games/Index')
            ->has('games', 1)
            ->where('games.0.name', 'First Table')
            ->where('games.0.role', GameRole::Gm->value)
        );
        $response->assertDontSee('Other Table');
    }

    public function test_second_user_can_join_game_from_invite_link(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($owner)->post(route('games.store'), [
            'name' => 'Lantern Keep',
            'description' => 'Fog and secrets',
        ]);

        $game = $owner->games()->firstOrFail();

        $this->actingAs($owner)->post(route('games.invites.store', $game));

        $invite = GameInvite::query()->where('game_id', $game->id)->firstOrFail();

        $response = $this->actingAs($player)->post(route('invites.accept', $invite->token));

        $response->assertRedirect(route('games.character.edit', $game, absolute: false));

        $this->assertDatabaseHas('game_members', [
            'game_id' => $game->id,
            'user_id' => $owner->id,
            'role' => GameRole::Gm->value,
        ]);

        $this->assertDatabaseHas('game_members', [
            'game_id' => $game->id,
            'user_id' => $player->id,
            'role' => GameRole::Player->value,
        ]);
    }

    public function test_owner_can_assign_co_gm_and_co_gm_sees_master_controls(): void
    {
        $owner = User::factory()->create();
        $coGm = User::factory()->create();

        $this->actingAs($owner)->post(route('games.store'), [
            'name' => 'Ash Court',
            'description' => 'Role control',
        ]);

        $game = $owner->games()->firstOrFail();
        $member = $game->members()->create([
            'user_id' => $coGm->id,
            'role' => GameRole::Player,
        ]);

        $this->actingAs($owner)->patch(route('games.members.role.update', [$game, $member]), [
            'role' => GameRole::CoGm->value,
        ])->assertRedirect(route('games.show', $game, absolute: false));

        $this->assertDatabaseHas('game_members', [
            'id' => $member->id,
            'role' => GameRole::CoGm->value,
        ]);

        $this->actingAs($coGm)->get(route('games.show', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Show')
                ->where('game.current_user_role', GameRole::CoGm->value)
                ->where('game.can_manage_invites', true)
                ->where('game.can_manage_content', true)
                ->where('game.can_manage_sessions', true)
                ->where('game.can_manage_member_roles', false)
            );
    }

    public function test_co_gm_can_manage_sessions_but_cannot_change_roles_or_owner_membership(): void
    {
        $owner = User::factory()->create();
        $coGm = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($owner)->post(route('games.store'), [
            'name' => 'Lantern Hall',
            'description' => 'Co-GM',
        ]);

        $game = $owner->games()->firstOrFail();
        $ownerMember = $game->members()->where('user_id', $owner->id)->firstOrFail();
        $coGmMember = $game->members()->create([
            'user_id' => $coGm->id,
            'role' => GameRole::CoGm,
        ]);
        $playerMember = $game->members()->create([
            'user_id' => $player->id,
            'role' => GameRole::Player,
        ]);

        $this->actingAs($coGm)->post(route('games.sessions.store', $game), [
            'title' => 'Master Access',
        ])->assertRedirect();

        $session = GameSession::query()->firstOrFail();

        $this->actingAs($coGm)->post(route('games.sessions.start', [$game, $session]))
            ->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->actingAs($coGm)->patch(route('games.members.role.update', [$game, $playerMember]), [
            'role' => GameRole::CoGm->value,
        ])->assertForbidden();

        $this->actingAs($owner)->patch(route('games.members.role.update', [$game, $playerMember]), [
            'role' => GameRole::CoGm->value,
        ])->assertRedirect();

        $this->actingAs($coGm)->patch(route('games.members.role.update', [$game, $ownerMember]), [
            'role' => GameRole::Player->value,
        ])->assertForbidden();

        $this->assertDatabaseHas('game_members', [
            'id' => $ownerMember->id,
            'role' => GameRole::Gm->value,
        ]);

        $this->assertDatabaseHas('game_members', [
            'id' => $coGmMember->id,
            'role' => GameRole::CoGm->value,
        ]);
    }
}
