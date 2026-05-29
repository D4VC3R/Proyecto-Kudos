<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para generar datos de prueba para el modelo Proposal.
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    /**
     * Define el estado por defecto de los atributos de una propuesta.
     * Genera datos aleatorios para el nombre, descripción, imágenes, estado, creador y categoría.
     *
     * @return array Los atributos de la propuesta con datos generados.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(3),
            'images' => [],
            'status' => Proposal::STATUS_PENDING,
            'creator_id' => User::inRandomOrder()->first()?->id,
            'category_id' => Category::inRandomOrder()->first()?->id,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_notes' => null,
        ];
    }

    /**
     * Define un estado para una propuesta aceptada, actualizando el estado, el revisor, la fecha de revisión y las notas del administrador.
     *
     * @param User|null $admin El usuario administrador que revisó la propuesta (opcional).
     * @return static La instancia de la fábrica con el estado actualizado a aceptado.
     */
    public function accepted(?User $admin = null): static
    {
        return $this->state(function () use ($admin) {
            return [
                'status' => Proposal::STATUS_ACCEPTED,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
                'admin_notes' => null,
            ];
        });
    }

    /**
     * Define un estado para una propuesta rechazada, actualizando el estado, el revisor, la fecha de revisión y las notas del administrador.
     *
     * @param User|null $admin El usuario administrador que revisó la propuesta (opcional).
     * @return static La instancia de la fábrica con el estado actualizado a rechazado.
     */
    public function rejected(?User $admin = null): static
    {
        return $this->state(function () use ($admin) {
            return [
                'status' => Proposal::STATUS_REJECTED,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
                'admin_notes' => 'No cumple los criterios de calidad establecidos.',
            ];
        });
    }

    /**
     * Define un estado para una propuesta con cambios solicitados, actualizando el estado, el revisor, la fecha de revisión y las notas del administrador.
     *
     * @param User|null $admin El usuario administrador que revisó la propuesta (opcional).
     * @return static La instancia de la fábrica con el estado actualizado a cambios solicitados.
     */
    public function changesRequested(?User $admin = null): static
    {
        return $this->state(function () use ($admin) {
            return [
                'status' => Proposal::STATUS_CHANGES_REQUESTED,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
                'admin_notes' => 'Necesita más detalle y una descripción más completa.',
            ];
        });
    }
}
