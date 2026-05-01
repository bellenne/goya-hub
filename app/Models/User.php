<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedGames(): HasMany
    {
        return $this->hasMany(Game::class, 'owner_id');
    }

    public function gameMemberships(): HasMany
    {
        return $this->hasMany(GameMember::class);
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function createdGameInvites(): HasMany
    {
        return $this->hasMany(GameInvite::class, 'created_by_user_id');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function sessionParticipants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class);
    }

    public function sessionDiceRolls(): HasMany
    {
        return $this->hasMany(SessionDiceRoll::class);
    }

    public function gameNotes(): HasMany
    {
        return $this->hasMany(GameNote::class);
    }
}
