<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CharacterInventoryController;
use App\Http\Controllers\GameBackgroundController;
use App\Http\Controllers\GameCharacterTemplateController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameInviteController;
use App\Http\Controllers\GameItemController;
use App\Http\Controllers\GameMemberController;
use App\Http\Controllers\GameNpcController;
use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\GameSessionGmPresenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionDiceRollController;
use App\Http\Controllers\SessionSceneController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/invites/{token}', [GameInviteController::class, 'show'])->name('invites.show');
Route::middleware('auth')->get('/session-invites/{token}', [GameSessionController::class, 'joinByInvite'])->name('sessions.invites.show');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Home');
    })->name('home');

    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::post('/games', [GameController::class, 'store'])->name('games.store');
    Route::get('/games/{game}', [GameController::class, 'show'])->name('games.show');
    Route::get('/games/{game}/character-template', [GameCharacterTemplateController::class, 'edit'])->name('games.character-template.edit');
    Route::patch('/games/{game}/character-template', [GameCharacterTemplateController::class, 'update'])->name('games.character-template.update');
    Route::post('/games/{game}/invites', [GameInviteController::class, 'store'])->name('games.invites.store');
    Route::patch('/games/{game}/members/{member}/role', [GameMemberController::class, 'updateRole'])->name('games.members.role.update');
    Route::get('/games/{game}/character', [CharacterController::class, 'edit'])->name('games.character.edit');
    Route::post('/games/{game}/character', [CharacterController::class, 'upsert'])->name('games.character.upsert');
    Route::get('/games/{game}/characters', [CharacterController::class, 'index'])->name('games.characters.index');
    Route::get('/games/{game}/characters/{character}', [CharacterController::class, 'show'])->name('games.characters.show');
    Route::patch('/games/{game}/characters/{character}/sheet', [CharacterController::class, 'updateSheet'])->name('games.characters.sheet.update');
    Route::post('/games/{game}/characters/{character}/inventory', [CharacterInventoryController::class, 'store'])->name('games.characters.inventory.store');
    Route::patch('/games/{game}/characters/{character}/inventory/{inventoryItem}', [CharacterInventoryController::class, 'update'])->name('games.characters.inventory.update');
    Route::delete('/games/{game}/characters/{character}/inventory/{inventoryItem}', [CharacterInventoryController::class, 'destroy'])->name('games.characters.inventory.destroy');
    Route::get('/games/{game}/npcs', [GameNpcController::class, 'index'])->name('games.npcs.index');
    Route::post('/games/{game}/npcs', [GameNpcController::class, 'store'])->name('games.npcs.store');
    Route::get('/games/{game}/npcs/{npc}/edit', [GameNpcController::class, 'edit'])->name('games.npcs.edit');
    Route::patch('/games/{game}/npcs/{npc}', [GameNpcController::class, 'update'])->name('games.npcs.update');
    Route::delete('/games/{game}/npcs/{npc}', [GameNpcController::class, 'destroy'])->name('games.npcs.destroy');
    Route::patch('/games/{game}/npcs/{npc}/type', [GameNpcController::class, 'updateType'])->name('games.npcs.type.update');
    Route::get('/games/{game}/items', [GameItemController::class, 'index'])->name('games.items.index');
    Route::post('/games/{game}/items', [GameItemController::class, 'store'])->name('games.items.store');
    Route::get('/games/{game}/items/{item}/edit', [GameItemController::class, 'edit'])->name('games.items.edit');
    Route::patch('/games/{game}/items/{item}', [GameItemController::class, 'update'])->name('games.items.update');
    Route::delete('/games/{game}/items/{item}', [GameItemController::class, 'destroy'])->name('games.items.destroy');
    Route::get('/games/{game}/backgrounds', [GameBackgroundController::class, 'index'])->name('games.backgrounds.index');
    Route::post('/games/{game}/backgrounds', [GameBackgroundController::class, 'store'])->name('games.backgrounds.store');
    Route::get('/games/{game}/backgrounds/{background}/edit', [GameBackgroundController::class, 'edit'])->name('games.backgrounds.edit');
    Route::patch('/games/{game}/backgrounds/{background}', [GameBackgroundController::class, 'update'])->name('games.backgrounds.update');
    Route::get('/games/{game}/sessions', [GameSessionController::class, 'index'])->name('games.sessions.index');
    Route::post('/games/{game}/sessions', [GameSessionController::class, 'store'])->name('games.sessions.store');
    Route::post('/games/{game}/sessions/join-by-code', [GameSessionController::class, 'joinByCode'])->name('games.sessions.join-by-code');
    Route::get('/games/{game}/sessions/{session}', [GameSessionController::class, 'show'])->name('games.sessions.show');
    Route::post('/games/{game}/sessions/{session}/join', [GameSessionController::class, 'join'])->name('games.sessions.join');
    Route::post('/games/{game}/sessions/{session}/start', [GameSessionController::class, 'start'])->name('games.sessions.start');
    Route::post('/games/{game}/sessions/{session}/gm-presence/connect', [GameSessionGmPresenceController::class, 'connect'])->name('games.sessions.gm-presence.connect');
    Route::post('/games/{game}/sessions/{session}/gm-presence/heartbeat', [GameSessionGmPresenceController::class, 'heartbeat'])->name('games.sessions.gm-presence.heartbeat');
    Route::post('/games/{game}/sessions/{session}/gm-presence/disconnect', [GameSessionGmPresenceController::class, 'disconnect'])->name('games.sessions.gm-presence.disconnect');
    Route::patch('/games/{game}/sessions/{session}/scene', [SessionSceneController::class, 'update'])->name('games.sessions.scene.update');
    Route::post('/games/{game}/sessions/{session}/scene-npcs', [SessionSceneController::class, 'storeNpc'])->name('games.sessions.scene-npcs.store');
    Route::patch('/games/{game}/sessions/{session}/scene-npcs/{sceneNpc}', [SessionSceneController::class, 'updateNpc'])->name('games.sessions.scene-npcs.update');
    Route::post('/games/{game}/sessions/{session}/dice-rolls', [SessionDiceRollController::class, 'store'])->name('games.sessions.dice-rolls.store');
    Route::post('/invites/{token}/accept', [GameInviteController::class, 'accept'])->name('invites.accept');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
