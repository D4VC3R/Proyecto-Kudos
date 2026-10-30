<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * Representa el perfil de un usuario en la aplicación.
 * Cada perfil pertenece a un usuario y contiene información adicional como avatar, biografía, enlaces sociales, ciudad y fecha de nacimiento.
 * También guarda los intereses del usuario (slugs de categoría elegidos en el onboarding, F2-07).
 * Proporciona una relación de pertenencia con el modelo User para acceder al usuario asociado a este perfil.
 */
class Profile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'avatar',
        'biography',
        'social_links',
        'city',
        'birthdate',
        'interests'
    ];

    protected $casts = [
        'social_links' => 'array',
        'birthdate' => 'date',
        'interests' => 'array'
    ];

    /*
     * Define la relación de pertenencia con el modelo User.
     * Permite acceder al usuario asociado a este perfil.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
