<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Proposal>
 */
class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(3),
            'images' => [
                [
                    'path' => fake()->imageUrl(640, 480),
                    'disk' => 'public',
                    'alt' => null,
                    'order' => 0,
                ],
            ],
            'status' => Proposal::STATUS_PENDING,
            'creator_id' => User::inRandomOrder()->first()?->id,
            'category_id' => Category::inRandomOrder()->first()?->id,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_notes' => null,
        ];
    }

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

    public function changesRequested(?User $admin = null): static
    {
        return $this->state(function () use ($admin) {
            return [
                'status' => Proposal::STATUS_CHANGES_REQUESTED,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
                'admin_notes' => 'Necesita mas detalle y una descripcion mas completa.',
            ];
        });
    }
}

