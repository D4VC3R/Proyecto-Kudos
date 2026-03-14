<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ProposalSeeder extends Seeder
{
    private const TOTAL_PROPOSALS = 30;

    public function run(): void
    {
        $proposalService = app(ProposalService::class);

        $admin = User::role('admin')->where('email', 'admin@kudos.com')->first();
        $users = User::role('user')->get();
        $categories = Category::all();

        if (!$admin) {
            $this->command?->error('No existe el admin del sistema para revisar propuestas.');
            return;
        }

        if ($users->isEmpty() || $categories->isEmpty()) {
            $this->command?->error('No hay usuarios o categorias suficientes para crear propuestas.');
            return;
        }

        $statusPlan = [
            Proposal::STATUS_ACCEPTED => 6,
            Proposal::STATUS_REJECTED => 12,
            Proposal::STATUS_CHANGES_REQUESTED => 9,
            Proposal::STATUS_PENDING => 3,
        ];

        $created = 0;

        foreach ($statusPlan as $targetStatus => $amount) {
            for ($i = 0; $i < $amount; $i++) {
                $creator = $users->random();
                $category = $categories->random();

                $draft = Proposal::factory()->make([
                    'category_id' => $category->id,
                ]);

                $proposal = $proposalService->createProposal(
                    Arr::only($draft->toArray(), ['name', 'description', 'images', 'category_id']),
                    $creator,
                );

                if ($targetStatus !== Proposal::STATUS_PENDING) {
                    $proposalService->review(
                        $proposal,
                        $admin,
                        $targetStatus,
                        $this->adminNotesForStatus($targetStatus),
                    );
                }

                $created++;
            }
        }

        $this->command?->info("{$created} propuestas generadas (objetivo: " . self::TOTAL_PROPOSALS . ').');
    }

    private function adminNotesForStatus(string $status): ?string
    {
        return match ($status) {
            Proposal::STATUS_REJECTED => 'La propuesta no cumple los criterios de moderacion.',
            Proposal::STATUS_CHANGES_REQUESTED => 'Ajusta la descripcion y mejora la calidad de las imagenes.',
            default => null,
        };
    }
}

