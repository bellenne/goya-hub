<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Enums\SessionStatus;
use App\Events\SessionDiceRolled;
use App\Models\Character;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SessionDiceRollTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_roll_dice_and_roll_is_saved_in_session_log(): void
    {
        Event::fake([SessionDiceRolled::class]);

        [$game, $gm, $player, $session] = $this->createActiveSession();

        $response = $this->actingAs($player)->post(route('games.sessions.dice-rolls.store', [$game, $session]), [
            'dice_count' => 2,
            'dice_type' => 'd6',
            'modifier' => 1,
        ]);

        $response->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->assertDatabaseHas('session_dice_rolls', [
            'game_session_id' => $session->id,
            'user_id' => $player->id,
            'dice_count' => 2,
            'dice_type' => 'd6',
            'modifier' => 1,
        ]);

        $roll = $session->fresh()->diceRolls()->latest()->firstOrFail();

        $this->assertCount(2, $roll->roll_values);
        $this->assertSame(array_sum($roll->roll_values) + 1, $roll->total);

        $this->actingAs($gm)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('rolls.items.0.user.id', $player->id)
                ->where('rolls.items.0.dice_count', 2)
                ->where('rolls.items.0.dice_type', 'd6')
                ->where('rolls.items.0.modifier', 1)
            );

        Event::assertDispatched(SessionDiceRolled::class);
    }

    public function test_gm_roll_is_visible_to_players_after_reopening_session(): void
    {
        Event::fake([SessionDiceRolled::class]);

        [$game, $gm, $player, $session] = $this->createActiveSession();

        $this->actingAs($gm)->post(route('games.sessions.dice-rolls.store', [$game, $session]), [
            'dice_count' => 1,
            'dice_type' => 'd20',
            'modifier' => 0,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $this->actingAs($player)->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('rolls.items.0.user.id', $gm->id)
                ->where('rolls.items.0.dice_type', 'd20')
                ->where('rolls.items.0.dice_count', 1)
                ->has('rolls.items.0.roll_values', 1)
            );
    }

    public function test_roll_uses_random_org_values_when_configured(): void
    {
        Event::fake([SessionDiceRolled::class]);
        Http::fake([
            'api.random.org/*' => Http::response([
                'jsonrpc' => '2.0',
                'result' => [
                    'random' => [
                        'data' => [4, 6],
                    ],
                ],
                'id' => 'dice-test',
            ]),
        ]);
        config()->set('services.random_org.api_key', 'test-key');

        [$game, , $player, $session] = $this->createActiveSession();

        $this->actingAs($player)->post(route('games.sessions.dice-rolls.store', [$game, $session]), [
            'dice_count' => 2,
            'dice_type' => 'd6',
            'modifier' => 1,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $roll = $session->fresh()->diceRolls()->latest()->firstOrFail();

        $this->assertSame([4, 6], $roll->roll_values);
        $this->assertSame(11, $roll->total);
        $this->assertSame('random_org', $roll->random_source);

        Http::assertSent(fn ($request) => $request['method'] === 'generateIntegers'
            && $request['params']['apiKey'] === 'test-key'
            && $request['params']['n'] === 2
            && $request['params']['min'] === 1
            && $request['params']['max'] === 6);
    }

    public function test_roll_falls_back_to_server_random_int_when_random_org_fails(): void
    {
        Event::fake([SessionDiceRolled::class]);
        Http::fake([
            'api.random.org/*' => Http::response(['error' => ['message' => 'quota exceeded']], 200),
        ]);
        config()->set('services.random_org.api_key', 'test-key');
        config()->set('services.random_org.fallback', 'local');

        [$game, , $player, $session] = $this->createActiveSession();

        $this->actingAs($player)->post(route('games.sessions.dice-rolls.store', [$game, $session]), [
            'dice_count' => 1,
            'dice_type' => 'd20',
            'modifier' => 0,
        ])->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));

        $roll = $session->fresh()->diceRolls()->latest()->firstOrFail();

        $this->assertCount(1, $roll->roll_values);
        $this->assertGreaterThanOrEqual(1, $roll->roll_values[0]);
        $this->assertLessThanOrEqual(20, $roll->roll_values[0]);
        $this->assertSame('server_random_int_fallback', $roll->random_source);
        $this->assertSame('quota exceeded', $roll->random_error);
    }

    public function test_roll_requires_active_session(): void
    {
        [$game, $gm, $player, $session] = $this->createActiveSession(status: SessionStatus::Lobby);

        $this->actingAs($player)->post(route('games.sessions.dice-rolls.store', [$game, $session]), [
            'dice_count' => 2,
            'dice_type' => 'd6',
            'modifier' => 1,
        ])->assertStatus(409);
    }

    protected function createActiveSession(SessionStatus $status = SessionStatus::Active): array
    {
        $gm = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($gm)->post(route('games.store'), [
            'name' => 'Dice Table',
            'description' => 'Dice tests',
        ]);

        /** @var Game $game */
        $game = Game::query()->firstOrFail();
        $game->members()->create([
            'user_id' => $player->id,
            'role' => GameRole::Player,
        ]);

        Character::query()->create([
            'game_id' => $game->id,
            'user_id' => $player->id,
            'name' => 'Rune Vale',
            'origin' => 'North Dock',
            'notes' => 'Carries a weathered coin.',
            'attribute_values' => ['strength' => 2, 'agility' => 3, 'mind' => 3],
            'skill_values' => ['observation' => 2, 'stealth' => 1, 'persuasion' => 1, 'tracking' => 0],
            'extra_field_values' => ['calling' => 'Scout', 'luck' => 1, 'motto' => 'Listen first.'],
            'is_active' => true,
        ]);

        $session = $game->sessions()->create([
            'title' => 'Roll Log',
            'invite_code' => 'ROLL42',
            'invite_token' => str_repeat('c', 64),
            'status' => $status,
        ]);

        $session->participants()->create([
            'user_id' => $gm->id,
            'joined_at' => now()->subMinute(),
        ]);

        $session->participants()->create([
            'user_id' => $player->id,
            'joined_at' => now(),
        ]);

        return [$game, $gm, $player, $session];
    }
}
