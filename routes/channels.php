<?php

use App\Models\GameSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web', 'auth']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('session.lobby.{sessionId}', function ($user, int $sessionId) {
    $session = GameSession::query()->with('game.members')->find($sessionId);

    if ($session === null || ! $session->game->members->contains('user_id', $user->id)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ];
});

Broadcast::channel('session.scene.{sessionId}', function ($user, int $sessionId) {
    $session = GameSession::query()->with('game.members')->find($sessionId);

    if ($session === null || ! $session->game->members->contains('user_id', $user->id)) {
        return false;
    }

    return true;
});

Broadcast::channel('session.rolls.{sessionId}', function ($user, int $sessionId) {
    $session = GameSession::query()->with('game.members')->find($sessionId);

    if ($session === null || ! $session->game->members->contains('user_id', $user->id)) {
        return false;
    }

    return true;
});

Broadcast::channel('session.inventory.{sessionId}', function ($user, int $sessionId) {
    $session = GameSession::query()->with('game.members')->find($sessionId);

    if ($session === null || ! $session->game->members->contains('user_id', $user->id)) {
        return false;
    }

    return true;
});
