<?php

namespace App\Models;

/**
 * Modelo User:
 * Los usuarios tienen dos roles, 'user' o 'admin', roles que se manejan con Spatie.
 * Un user puede ganar puntos Kudos votando elementos (ítems) de distintas categorías
 * y creando nuevos ítems si un usuario administrador los acepta.
 *
 *  */

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasUuids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'total_kudos' => 0,
        'creations_accepted' => 0,
    ];

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

		// Relaciones
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
		public function items(): HasMany
		{
			return $this->hasMany(Item::class, 'creator_id');
		}
		public function proposals(): HasMany
		{
			return $this->hasMany(Proposal::class, 'creator_id');
		}
		public function reviewedProposals(): HasMany
		{
			return $this->hasMany(Proposal::class, 'reviewed_by');
		}
		public function votes(): HasMany
		{
			return $this->hasMany(Vote::class);
		}
		public function kudosTransactions(): HasMany
		{
			return $this->hasMany(KudosTransaction::class);
		}
}
