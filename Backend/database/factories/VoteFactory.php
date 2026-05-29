<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para generar datos de prueba para el modelo Vote.
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    /**
     * Define el estado por defecto de los atributos de un voto.
     * Genera datos aleatorios para el usuario, el item, el tipo de voto y la puntuación.
     *
     * @return array Los atributos del voto con datos generados.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'item_id' => Item::where('status', Item::STATUS_ACTIVE)->inRandomOrder()->first()->id,
            'type' => Vote::TYPE_VOTE,
            'score' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Define un estado para un voto asociado a un ítem específico.
     *
     * @param Item $item El ítem al que se asociará el voto.
     * @return static La instancia de la fábrica con el estado actualizado para el ítem especificado.
     */
    public function forItem(Item $item): static
    {
        return $this->state(fn (array $attributes) => [
            'item_id' => $item->id,
        ]);
    }

    /**
     * Define un estado para un voto asociado a un usuario específico.
     *
     * @param User $user El usuario al que se asociará el voto.
     * @return static La instancia de la fábrica con el estado actualizado para el usuario especificado.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Define un estado para un voto con una puntuación específica, asegurando que la puntuación esté entre 0 y 10.
     *
     * @param int $score La puntuación del voto (entre 0 y 10).
     * @return static La instancia de la fábrica con el estado actualizado para la puntuación especificada.
     */
    public function withScore(int $score): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => max(0, min(10, $score)),
        ]);
    }
}
