<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Enums\SessionMusicPlaybackStatus;
use App\Enums\SessionMusicSourceType;
use App\Enums\SessionStatus;
use App\Events\SessionSceneUpdated;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SessionMusicTest extends TestCase
{
    use RefreshDatabase;

    public function test_gm_can_set_direct_music_source_and_players_receive_scene_state(): void
    {
        Event::fake([SessionSceneUpdated::class]);
        [$game, $gm, $player, $session] = $this->createActiveSession();

        $this->actingAs($gm)->postJson(route('games.sessions.music.source.update', [$game, $session]), [
            'source_type' => SessionMusicSourceType::DirectUrl->value,
            'title' => 'Forest tension',
            'direct_url' => 'https://example.com/forest.mp3',
        ])->assertOk()
            ->assertJsonPath('music.source_type', SessionMusicSourceType::DirectUrl->value)
            ->assertJsonPath('music.audio_url', 'https://example.com/forest.mp3');

        $this->actingAs($gm)->patchJson(route('games.sessions.music.playback.update', [$game, $session]), [
            'playback_status' => SessionMusicPlaybackStatus::Playing->value,
            'position_seconds' => 12,
        ])->assertOk()
            ->assertJsonPath('music.playback_status', SessionMusicPlaybackStatus::Playing->value)
            ->assertJsonPath('music.position_seconds', 12);

        $this->actingAs($player)
            ->get(route('games.sessions.show', [$game, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Sessions/Table')
                ->where('scene.music.title', 'Forest tension')
                ->where('scene.music.source_type', SessionMusicSourceType::DirectUrl->value)
                ->where('scene.music.playback_status', SessionMusicPlaybackStatus::Playing->value)
            );

        Event::assertDispatched(SessionSceneUpdated::class);
    }

    public function test_gm_can_upload_music_track(): void
    {
        Storage::fake('public');
        [$game, $gm, $player, $session] = $this->createActiveSession();

        $this->actingAs($gm)->postJson(route('games.sessions.music.source.update', [$game, $session]), [
            'source_type' => SessionMusicSourceType::Uploaded->value,
            'title' => 'Battle drums',
            'track' => UploadedFile::fake()->create('drums.mp3', 128, 'application/octet-stream'),
        ])->assertOk()
            ->assertJsonPath('music.source_type', SessionMusicSourceType::Uploaded->value)
            ->assertJsonPath('music.title', 'Battle drums');

        $state = $session->refresh()->musicState;

        $this->assertNotNull($state->file_path);
        Storage::disk('public')->assertExists($state->file_path);
    }

    public function test_gm_can_set_youtube_music_source(): void
    {
        [$game, $gm, $player, $session] = $this->createActiveSession();

        $this->actingAs($gm)->postJson(route('games.sessions.music.source.update', [$game, $session]), [
            'source_type' => SessionMusicSourceType::Youtube->value,
            'title' => 'Dungeon ambience',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertOk()
            ->assertJsonPath('music.source_type', SessionMusicSourceType::Youtube->value)
            ->assertJsonPath('music.youtube_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
    }

    public function test_player_cannot_change_global_session_music(): void
    {
        [$game, $gm, $player, $session] = $this->createActiveSession();

        $this->actingAs($player)->postJson(route('games.sessions.music.source.update', [$game, $session]), [
            'source_type' => SessionMusicSourceType::DirectUrl->value,
            'direct_url' => 'https://example.com/player.mp3',
        ])->assertForbidden();

        $this->actingAs($player)->patchJson(route('games.sessions.music.playback.update', [$game, $session]), [
            'playback_status' => SessionMusicPlaybackStatus::Playing->value,
        ])->assertForbidden();
    }

    protected function createActiveSession(): array
    {
        $gm = User::factory()->create();
        $player = User::factory()->create();

        /** @var Game $game */
        $game = Game::query()->create([
            'owner_id' => $gm->id,
            'name' => 'Music Table',
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

        /** @var GameSession $session */
        $session = $game->sessions()->create([
            'title' => 'Music Session',
            'invite_code' => 'MUSIC1',
            'invite_token' => str_repeat('m', 64),
            'status' => SessionStatus::Active,
            'started_at' => now(),
        ]);

        return [$game, $gm, $player, $session];
    }
}
