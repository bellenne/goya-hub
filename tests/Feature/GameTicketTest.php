<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Enums\TicketStatus;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_create_ticket_and_open_thread(): void
    {
        [$game, $gm, $player] = $this->createGameWithMembers();

        $response = $this->actingAs($player)->post(route('games.tickets.store', $game), [
            'title' => 'Secret door question',
            'body' => 'Can I inspect the old arch between sessions?',
        ]);

        $ticket = Ticket::query()->firstOrFail();

        $response->assertRedirect(route('games.tickets.show', [$game, $ticket], absolute: false));
        $this->assertSame($game->id, $ticket->game_id);
        $this->assertSame($player->id, $ticket->creator_user_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame('Can I inspect the old arch between sessions?', $ticket->messages()->first()->body);

        $this->actingAs($player)
            ->get(route('games.tickets.show', [$game, $ticket]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Tickets/Show')
                ->where('ticket.title', 'Secret door question')
                ->where('ticket.messages.0.body', 'Can I inspect the old arch between sessions?')
                ->where('can_manage_tickets', false)
            );
    }

    public function test_player_sees_only_own_tickets_and_cannot_open_other_player_ticket(): void
    {
        [$game, $gm, $player, $otherPlayer] = $this->createGameWithMembers(includeSecondPlayer: true);
        $ownTicket = $this->createTicket($game, $player, 'My issue');
        $otherTicket = $this->createTicket($game, $otherPlayer, 'Other issue');

        $this->actingAs($player)
            ->get(route('games.tickets.index', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Tickets/Index')
                ->has('tickets', 1)
                ->where('tickets.0.id', $ownTicket->id)
            );

        $this->actingAs($player)
            ->get(route('games.tickets.show', [$game, $otherTicket]))
            ->assertForbidden();
    }

    public function test_gm_can_view_all_tickets_reply_and_change_status(): void
    {
        [$game, $gm, $player, $otherPlayer] = $this->createGameWithMembers(includeSecondPlayer: true);
        $ticket = $this->createTicket($game, $player, 'Downtime');
        $this->createTicket($game, $otherPlayer, 'Loot split');

        $this->actingAs($gm)
            ->get(route('games.tickets.index', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Tickets/Index')
                ->has('tickets', 2)
                ->where('can_manage_tickets', true)
            );

        $this->actingAs($gm)
            ->postJson(route('games.tickets.messages.store', [$game, $ticket]), [
                'body' => 'Yes, make an Investigation roll at session start.',
            ])
            ->assertOk()
            ->assertJsonPath('message.body', 'Yes, make an Investigation roll at session start.');

        $this->actingAs($gm)
            ->patchJson(route('games.tickets.status.update', [$game, $ticket]), [
                'status' => TicketStatus::WaitingPlayer->value,
            ])
            ->assertOk()
            ->assertJsonPath('ticket.status', TicketStatus::WaitingPlayer->value);

        $ticket->refresh();

        $this->assertSame(TicketStatus::WaitingPlayer, $ticket->status);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_user_id' => $gm->id,
            'body' => 'Yes, make an Investigation roll at session start.',
        ]);
    }

    public function test_player_cannot_change_ticket_status(): void
    {
        [$game, $gm, $player] = $this->createGameWithMembers();
        $ticket = $this->createTicket($game, $player, 'Rules question');

        $this->actingAs($player)
            ->patchJson(route('games.tickets.status.update', [$game, $ticket]), [
                'status' => TicketStatus::Closed->value,
            ])
            ->assertForbidden();

        $this->assertSame(TicketStatus::Open, $ticket->refresh()->status);
    }

    public function test_gm_cannot_access_tickets_from_another_game(): void
    {
        [$game, $gm, $player] = $this->createGameWithMembers();
        [$otherGame, $otherGm, $otherPlayer] = $this->createGameWithMembers('Other Game');
        $ticket = $this->createTicket($otherGame, $otherPlayer, 'Wrong table');

        $this->actingAs($gm)
            ->get(route('games.tickets.show', [$otherGame, $ticket]))
            ->assertForbidden();

        $this->actingAs($gm)
            ->get(route('games.tickets.show', [$game, $ticket]))
            ->assertNotFound();
    }

    protected function createGameWithMembers(string $name = 'Ticket Game', bool $includeSecondPlayer = false): array
    {
        $gm = User::factory()->create();
        $player = User::factory()->create();
        $otherPlayer = User::factory()->create();

        /** @var Game $game */
        $game = Game::query()->create([
            'owner_id' => $gm->id,
            'name' => $name,
            'description' => null,
        ]);

        $game->members()->create([
            'user_id' => $gm->id,
            'role' => GameRole::Gm,
        ]);
        $game->members()->create([
            'user_id' => $player->id,
            'role' => GameRole::Player,
        ]);

        if ($includeSecondPlayer) {
            $game->members()->create([
                'user_id' => $otherPlayer->id,
                'role' => GameRole::Player,
            ]);
        }

        return $includeSecondPlayer ? [$game, $gm, $player, $otherPlayer] : [$game, $gm, $player];
    }

    protected function createTicket(Game $game, User $creator, string $title): Ticket
    {
        /** @var Ticket $ticket */
        $ticket = $game->tickets()->create([
            'creator_user_id' => $creator->id,
            'title' => $title,
            'status' => TicketStatus::Open,
            'last_message_at' => now(),
            'last_message_user_id' => $creator->id,
        ]);

        $ticket->messages()->create([
            'author_user_id' => $creator->id,
            'body' => "Initial message for $title",
        ]);

        return $ticket;
    }
}
