<?php

namespace Tests\Feature;

use App\Enums\GameRole;
use App\Enums\SessionStatus;
use App\Models\Character;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CharacterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_open_character_form_and_save_character(): void
    {
        Storage::fake('public');

        [$game, $player] = $this->createGameWithPlayer();

        $this->actingAs($player)
            ->get(route('games.character.edit', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Games/Characters/Edit'));

        $response = $this->actingAs($player)->post(route('games.character.upsert', $game), [
            'name' => 'Aster Vale',
            'origin' => 'North March',
            'notes' => 'Keeps field journals.',
            'avatar' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sZ6uWAAAAAASUVORK5CYII='),
            ),
            'attribute_values' => [
                'strength' => 3,
                'agility' => 3,
                'mind' => 2,
            ],
            'skill_values' => [
                'observation' => 2,
                'stealth' => 1,
                'persuasion' => 2, 'tracking' => 0,
            ],
            'extra_field_values' => [
                'calling' => 'Scout',
                'luck' => 2,
                'motto' => 'Trust the quiet path.',
            ],
        ]);

        $response->assertRedirect(route('games.character.edit', $game, absolute: false));

        $character = Character::query()->where('game_id', $game->id)->where('user_id', $player->id)->first();

        $this->assertNotNull($character);
        $this->assertSame('Aster Vale', $character->name);
        $this->assertSame(3, $character->attribute_values['strength']);
        Storage::disk('public')->assertExists($character->avatar_path);
    }

    public function test_gm_can_view_character_list_and_created_character(): void
    {
        [$game, $player, $owner] = $this->createGameWithPlayer(includeOwner: true);

        Character::query()->create([
            'game_id' => $game->id,
            'user_id' => $player->id,
            'name' => 'Mira Stone',
            'origin' => 'River District',
            'notes' => 'Observes before acting.',
            'attribute_values' => ['strength' => 2, 'agility' => 3, 'mind' => 3],
            'skill_values' => ['observation' => 2, 'stealth' => 1, 'persuasion' => 1, 'tracking' => 0],
            'extra_field_values' => ['calling' => 'Investigator', 'luck' => 1, 'motto' => 'Patterns never lie.'],
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('games.characters.index', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Characters/Index')
                ->has('characters', 1)
                ->where('characters.0.name', 'Mira Stone')
            );
    }

    public function test_free_points_limit_cannot_be_exceeded(): void
    {
        [$game, $player] = $this->createGameWithPlayer();
        $this->applyAttributeTemplate($game);

        $response = $this->actingAs($player)->post(route('games.character.upsert', $game), [
            'name' => 'Broken Build',
            'attribute_values' => [
                'strength' => 5,
                'agility' => 5,
                'mind' => 5,
            ],
            'skill_values' => [
                'observation' => 0,
                'stealth' => 0,
                'persuasion' => 0, 'tracking' => 0,
            ],
            'extra_field_values' => [
                'calling' => 'None',
                'luck' => 0,
                'motto' => '',
            ],
        ]);

        $response->assertSessionHasErrors('attribute_values');
        $this->assertDatabaseCount('characters', 0);
    }

    public function test_lowering_attribute_below_default_returns_points_for_character_creation(): void
    {
        [$game, $player] = $this->createGameWithPlayer();
        $this->applyAttributeTemplate($game, points: 2, default: -2, min: -5, max: 5);

        $response = $this->actingAs($player)->post(route('games.character.upsert', $game), [
            'name' => 'Balanced Weakness',
            'attribute_values' => [
                'strength' => -5,
                'agility' => 3,
                'mind' => -2,
            ],
            'skill_values' => ['unused' => 0],
            'extra_field_values' => [],
        ]);

        $response->assertRedirect(route('games.character.edit', $game, absolute: false));

        $character = Character::query()->where('game_id', $game->id)->where('user_id', $player->id)->firstOrFail();

        $this->assertSame(-5, $character->attribute_values['strength']);
        $this->assertSame(3, $character->attribute_values['agility']);
    }

    public function test_attribute_values_must_stay_within_template_min_and_max(): void
    {
        [$game, $player] = $this->createGameWithPlayer();
        $this->applyAttributeTemplate($game, points: 2, default: -2, min: -5, max: 5);

        $response = $this->actingAs($player)->post(route('games.character.upsert', $game), [
            'name' => 'Too Low',
            'attribute_values' => [
                'strength' => -6,
                'agility' => -2,
                'mind' => -2,
            ],
            'skill_values' => ['unused' => 0],
            'extra_field_values' => [],
        ]);

        $response->assertSessionHasErrors('attribute_values.strength');
        $this->assertDatabaseCount('characters', 0);
    }

    public function test_character_sheet_update_validates_returned_and_spent_attribute_points(): void
    {
        [$game, $player, $owner] = $this->createGameWithPlayer(includeOwner: true);
        $this->applyAttributeTemplate($game, points: 2, default: -2, min: -5, max: 5);

        $character = Character::query()->create([
            'game_id' => $game->id,
            'user_id' => $player->id,
            'name' => 'Session Sheet',
            'origin' => null,
            'notes' => null,
            'attribute_values' => ['strength' => -2, 'agility' => -2, 'mind' => -2],
            'skill_values' => ['unused' => 0],
            'extra_field_values' => [],
            'is_active' => true,
        ]);

        $this->actingAs($owner)->patch(route('games.characters.sheet.update', [$game, $character]), [
            'attribute_values' => ['strength' => -5, 'agility' => 3, 'mind' => -2],
            'skill_values' => ['unused' => 0],
            'extra_field_values' => [],
        ])->assertRedirect(route('games.characters.show', [$game, $character], absolute: false));

        $this->assertSame(3, $character->refresh()->attribute_values['agility']);

        $this->actingAs($owner)->patch(route('games.characters.sheet.update', [$game, $character]), [
            'attribute_values' => ['strength' => -2, 'agility' => 5, 'mind' => 5],
            'skill_values' => ['unused' => 0],
            'extra_field_values' => [],
        ])->assertSessionHasErrors('attribute_values');
    }

    public function test_reopening_form_shows_saved_character_data(): void
    {
        [$game, $player] = $this->createGameWithPlayer();

        Character::query()->create([
            'game_id' => $game->id,
            'user_id' => $player->id,
            'name' => 'Tarin Ash',
            'origin' => 'Old Quarry',
            'notes' => 'Already saved.',
            'attribute_values' => ['strength' => 2, 'agility' => 2, 'mind' => 4],
            'skill_values' => ['observation' => 1, 'stealth' => 2, 'persuasion' => 1, 'tracking' => 0],
            'extra_field_values' => ['calling' => 'Archivist', 'luck' => 1, 'motto' => 'Write it down.'],
            'is_active' => true,
        ]);

        $this->actingAs($player)
            ->get(route('games.character.edit', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Characters/Edit')
                ->where('character.name', 'Tarin Ash')
                ->where('character.attribute_values.mind', 4)
                ->where('character.extra_field_values.calling', 'Archivist')
            );
    }

    public function test_gm_can_update_character_sheet_template_with_subskills_and_modifiers(): void
    {
        [$game, $player, $owner] = $this->createGameWithPlayer(includeOwner: true);

        $this->actingAs($owner)
            ->get(route('games.character-template.edit', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/CharacterTemplate/Edit')
                ->where('game.id', $game->id)
            );

        $response = $this->actingAs($owner)->patch(route('games.character-template.update', $game), [
            'attributes' => [
                'points' => 8,
                'items' => [
                    [
                        'label' => 'Might',
                        'key' => 'might',
                        'default' => 1,
                        'min' => 1,
                        'max' => 6,
                        'roll' => [
                            'enabled' => true,
                            'dice' => 'd20',
                            'modifier_step' => 2,
                        ],
                    ],
                ],
            ],
            'skills' => [
                'points' => 5,
                'items' => [
                    [
                        'label' => 'Lore',
                        'key' => 'lore',
                        'default' => 0,
                        'min' => 0,
                        'max' => 4,
                        'subskills' => [
                            ['label' => 'Arcana', 'key' => 'arcana', 'default' => 0, 'min' => 0, 'max' => 4],
                        ],
                    ],
                ],
            ],
            'points' => [
                'extras' => 2,
            ],
            'extra_fields' => [
                [
                    'label' => 'Oath',
                    'key' => 'oath',
                    'type' => 'textarea',
                    'required' => false,
                    'default' => '',
                    'max_length' => 500,
                ],
            ],
        ]);

        $response->assertRedirect(route('games.character-template.edit', $game, absolute: false));

        $game->refresh();
        $this->assertSame(8, $game->character_template['attributes']['points']);
        $this->assertSame('might', $game->character_template['attributes']['items'][0]['key']);
        $this->assertSame(2, $game->character_template['attributes']['items'][0]['roll']['modifier_step']);
        $this->assertSame('arcana', $game->character_template['skills']['items'][0]['subskills'][0]['key']);

        $this->actingAs($player)
            ->get(route('games.character.edit', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Games/Characters/Edit')
                ->where('template.attributes.items.0.key', 'might')
                ->where('template.skills.items.0.subskills.0.key', 'arcana')
                ->where('template.extra_fields.0.key', 'oath')
            );
    }

    public function test_character_creation_can_return_player_to_joined_session(): void
    {
        [$game, $player] = $this->createGameWithPlayer();

        $session = $game->sessions()->create([
            'title' => 'Opening Door',
            'invite_code' => 'ABC123',
            'invite_token' => str_repeat('b', 64),
            'status' => SessionStatus::Lobby,
        ]);

        $response = $this->actingAs($player)->post(route('games.character.upsert', $game), [
            'name' => 'Return Path',
            'attribute_values' => [
                'strength' => 2,
                'agility' => 2,
                'mind' => 2,
            ],
            'skill_values' => [
                'observation' => 1,
                'stealth' => 1,
                'persuasion' => 1, 'tracking' => 0,
            ],
            'extra_field_values' => [
                'calling' => 'Guide',
                'luck' => 1,
                'motto' => 'Keep moving.',
            ],
            'back_to_session_id' => $session->id,
        ]);

        $response->assertRedirect(route('games.sessions.show', [$game, $session], absolute: false));
    }

    protected function createGameWithPlayer(bool $includeOwner = false): array
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($owner)->post(route('games.store'), [
            'name' => 'Fog Hollow',
            'description' => 'Character test table',
        ]);

        /** @var Game $game */
        $game = Game::query()->firstOrFail();
        $game->members()->create([
            'user_id' => $player->id,
            'role' => GameRole::Player,
        ]);

        return $includeOwner ? [$game, $player, $owner] : [$game, $player];
    }

    protected function applyAttributeTemplate(Game $game, int $points = 2, int $default = 2, int $min = 0, int $max = 5): void
    {
        $game->update([
            'character_template' => [
                'attributes' => [
                    'points' => $points,
                    'items' => [
                        ['key' => 'strength', 'label' => 'Strength', 'default' => $default, 'min' => $min, 'max' => $max, 'player_editable' => true],
                        ['key' => 'agility', 'label' => 'Agility', 'default' => $default, 'min' => $min, 'max' => $max, 'player_editable' => true],
                        ['key' => 'mind', 'label' => 'Mind', 'default' => $default, 'min' => $min, 'max' => $max, 'player_editable' => true],
                    ],
                ],
                'skills' => [
                    'points' => 0,
                    'items' => [],
                ],
                'points' => [],
                'extra_fields' => [],
            ],
        ]);
    }
}
