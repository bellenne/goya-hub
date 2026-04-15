<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Enums\SessionStatus;
use App\Events\SessionLobbyUpdated;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_gm_can_create_session(): void
    {
        $gm = User::factory()->create();

        $this->actingAs($gm)->post(route('games.store'), [
            'name' => 'Lobby Table',
            'description' => 'Session game',
        ]);

        /** @var Game $game */
        $game = Game::query()->firstOrFail();

        $response = $this->actingAs($gm)->post(route('games.sessions.store', $game), [
            'title' => 'Session Zero',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('game_sessions', [
            'game_id' => $game->id,
            'title' => 'Session Zero',
            'status' => SessionStatus::Lobby->value,
        ]);
    }

    public function test_player_can_join_session_by_code_and_appear_in_lobby(): void
    {
        Event::fake([SessionLobbyUpdated::class]);

        [$game, $gm, $player, $session] = $this->createGameWithSession();

        $response = $this->actingAs($player)->post(route('games.sessions.join-by-code', $game), [
            'invite_code' => $session->invite_code,
        ]);

        $response->assertRedirect(route('games.character.edit', [
            'game' => $game,
            'back_to_session_id' => $session->id,
        ], absolute: false));
        $this->assertDatabaseHas('session_participants', [
            'game_session_id' => $session->id,
            'user_id' => $player->id,
        ]);

        $this->actingAs($gm)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Show')
                ->where('session.participants.0.user.id', $player->id)
            );

        Event::assertDispatched(SessionLobbyUpdated::class);
    }

    public function test_session_invite_link_can_auto_add_user_to_game_and_join_lobby(): void
    {
        Event::fake([SessionLobbyUpdated::class]);

        [$game, $gm, $player, $session] = $this->createGameWithSession(includePlayer: false);

        $response = $this->actingAs($player)->get(route('sessions.invites.show', $session->invite_token));

        $response->assertRedirect(route('games.character.edit', [
            'game' => $game,
            'back_to_session_id' => $session->id,
        ], absolute: false));

        $this->assertDatabaseHas('game_members', [
            'game_id' => $game->id,
            'user_id' => $player->id,
            'role' => GameRole::Player->value,
        ]);

        $this->assertDatabaseHas('session_participants', [
            'game_session_id' => $session->id,
            'user_id' => $player->id,
        ]);
    }

    public function test_gm_can_start_session_and_status_changes_to_active(): void
    {
        Event::fake([SessionLobbyUpdated::class]);

        [$game, $gm, $player, $session] = $this->createGameWithSession();

        $this->actingAs($player)->post(route('games.sessions.join-by-code', $game), [
            'invite_code' => $session->invite_code,
        ]);

        $response = $this->actingAs($gm)->post(route('games.sessions.start', [$game, $session]));

        $response->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));
        $this->assertDatabaseHas('game_sessions', [
            'id' => $session->id,
            'status' => SessionStatus::Active->value,
        ]);

        Event::assertDispatched(SessionLobbyUpdated::class);
    }

    protected function createGameWithSession(bool $includePlayer = true): array
    {
        $gm = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($gm)->post(route('games.store'), [
            'name' => 'Echo Table',
            'description' => 'Lobby tests',
        ]);

        /** @var Game $game */
        $game = Game::query()->firstOrFail();

        if ($includePlayer) {
            $game->members()->create([
                'user_id' => $player->id,
                'role' => GameRole::Player,
            ]);
        }

        $session = $game->sessions()->create([
            'title' => 'Opening Night',
            'invite_code' => 'ABC123',
            'invite_token' => str_repeat('a', 64),
            'status' => SessionStatus::Lobby,
        ]);

        return [$game, $gm, $player, $session];
    }
}
