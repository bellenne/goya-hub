<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameTicketController extends Controller
{
    public function index(Request $request, Game $game): Response
    {
        Gate::authorize('viewTickets', $game);

        $canManageTickets = Gate::allows('manageTickets', $game);

        $tickets = $game->tickets()
            ->with(['creator:id,name,email', 'lastMessage.author:id,name,email', 'lastMessageUser:id,name,email'])
            ->when(! $canManageTickets, fn ($query) => $query->where('creator_user_id', $request->user()->id))
            ->latest('last_message_at')
            ->latest('updated_at')
            ->get()
            ->map(fn (Ticket $ticket) => $this->ticketListPayload($ticket));

        return Inertia::render('Games/Tickets/Index', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'tickets' => $tickets,
            'statuses' => TicketStatus::options(),
            'can_manage_tickets' => $canManageTickets,
        ]);
    }

    public function store(StoreTicketRequest $request, Game $game): RedirectResponse
    {
        Gate::authorize('viewTickets', $game);

        $ticket = DB::transaction(function () use ($request, $game) {
            /** @var Ticket $ticket */
            $ticket = $game->tickets()->create([
                'creator_user_id' => $request->user()->id,
                'title' => $request->validated('title'),
                'status' => TicketStatus::Open,
                'last_message_at' => now(),
                'last_message_user_id' => $request->user()->id,
            ]);

            $ticket->messages()->create([
                'author_user_id' => $request->user()->id,
                'body' => $request->validated('body'),
            ]);

            return $ticket;
        });

        return redirect()
            ->route('games.tickets.show', [$game, $ticket])
            ->with('success', 'Тикет создан.');
    }

    public function show(Request $request, Game $game, Ticket $ticket): Response
    {
        $this->authorizeTicketAccess($request->user(), $game, $ticket);

        $ticket->load([
            'creator:id,name,email',
            'lastMessage.author:id,name,email',
            'lastMessageUser:id,name,email',
            'messages.author:id,name,email',
        ]);

        return Inertia::render('Games/Tickets/Show', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'ticket' => $this->ticketDetailPayload($ticket),
            'statuses' => TicketStatus::options(),
            'can_manage_tickets' => Gate::allows('manageTickets', $game),
        ]);
    }

    public function storeMessage(StoreTicketMessageRequest $request, Game $game, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorizeTicketAccess($request->user(), $game, $ticket);

        $message = DB::transaction(function () use ($request, $ticket) {
            /** @var TicketMessage $message */
            $message = $ticket->messages()->create([
                'author_user_id' => $request->user()->id,
                'body' => $request->validated('body'),
            ]);

            $ticket->update([
                'last_message_at' => $message->created_at,
                'last_message_user_id' => $request->user()->id,
            ]);

            return $message;
        });

        $ticket->refresh()->load(['creator:id,name,email', 'lastMessage.author:id,name,email', 'lastMessageUser:id,name,email']);
        $message->load('author:id,name,email');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->messagePayload($message),
                'ticket' => $this->ticketMetaPayload($ticket),
            ]);
        }

        return back()->with('success', 'Сообщение отправлено.');
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Game $game, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorizeTicketAccess($request->user(), $game, $ticket);
        Gate::authorize('manageTickets', $game);

        $status = TicketStatus::from($request->validated('status'));

        $ticket->update([
            'status' => $status,
            'closed_at' => $status === TicketStatus::Closed ? now() : null,
        ]);

        $ticket->refresh()->load(['creator:id,name,email', 'lastMessage.author:id,name,email', 'lastMessageUser:id,name,email']);

        if ($request->expectsJson()) {
            return response()->json([
                'ticket' => $this->ticketMetaPayload($ticket),
            ]);
        }

        return back()->with('success', 'Статус тикета обновлён.');
    }

    protected function authorizeTicketAccess(User $user, Game $game, Ticket $ticket): void
    {
        Gate::authorize('viewTickets', $game);
        abort_unless($ticket->game_id === $game->id, 404);

        if (Gate::forUser($user)->allows('manageTickets', $game)) {
            return;
        }

        abort_unless($ticket->creator_user_id === $user->id, 403);
    }

    protected function ticketListPayload(Ticket $ticket): array
    {
        return [
            ...$this->ticketMetaPayload($ticket),
            'preview' => str($ticket->lastMessage?->body ?? '')->squish()->limit(180)->toString(),
        ];
    }

    protected function ticketDetailPayload(Ticket $ticket): array
    {
        return [
            ...$this->ticketMetaPayload($ticket),
            'messages' => $ticket->messages
                ->sortBy('created_at')
                ->values()
                ->map(fn (TicketMessage $message) => $this->messagePayload($message)),
        ];
    }

    protected function ticketMetaPayload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'title' => $ticket->title,
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'status_tone' => $ticket->status->tone(),
            'creator' => $ticket->creator?->only(['id', 'name', 'email']),
            'last_message_at' => $ticket->last_message_at?->toISOString(),
            'last_message_user' => $ticket->lastMessageUser?->only(['id', 'name', 'email']),
            'created_at' => $ticket->created_at?->toISOString(),
            'updated_at' => $ticket->updated_at?->toISOString(),
            'closed_at' => $ticket->closed_at?->toISOString(),
        ];
    }

    protected function messagePayload(TicketMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'author' => $message->author?->only(['id', 'name', 'email']),
            'created_at' => $message->created_at?->toISOString(),
            'updated_at' => $message->updated_at?->toISOString(),
        ];
    }
}
