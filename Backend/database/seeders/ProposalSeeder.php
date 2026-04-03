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
                    [
                        ...Arr::only($draft->toArray(), ['name', 'description', 'images', 'category_id']),
                        'extra_data' => $this->buildExtraDataForCategory($category->slug),
                    ],
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

    /**
     * @return array<string,mixed>
     */
    private function buildExtraDataForCategory(string $slug): array
    {
        return match ($slug) {
            'videojuegos' => [
                'developer' => fake()->company(),
                'publisher' => fake()->company(),
                'platforms' => fake()->randomElements(['PC', 'PS5', 'Xbox Series', 'Switch'], fake()->numberBetween(1, 3)),
                'genre' => fake()->randomElement(['accion', 'aventura', 'rpg', 'estrategia', 'deportes', 'simulacion', 'indie']),
                'release_date' => fake()->date(),
                'metacritic_score' => fake()->numberBetween(60, 99),
            ],
            'peliculas' => [
                'director' => fake()->name(),
                'release_year' => fake()->numberBetween(1950, 2026),
                'actors' => [fake()->name(), fake()->name(), fake()->name()],
                'platforms' => fake()->randomElements(['netflix', 'prime video', 'max', 'disney+'], fake()->numberBetween(1, 3)),
            ],
            'series' => [
                'seasons' => fake()->numberBetween(1, 12),
                'platforms' => fake()->randomElements(['netflix', 'prime video', 'max', 'apple tv+'], fake()->numberBetween(1, 3)),
                'actors' => [fake()->name(), fake()->name(), fake()->name()],
                'release_year' => fake()->numberBetween(1970, 2026),
                'director' => fake()->name(),
            ],
            'ciudades' => [
                'country' => fake()->country(),
                'population' => fake()->numberBetween(100000, 25000000),
                'language' => fake()->languageCode(),
                'places_of_interest' => [fake()->streetName(), fake()->streetName()],
            ],
            'paises' => [
                'continent' => fake()->randomElement(['europa', 'asia', 'america', 'africa', 'oceania']),
                'population' => fake()->numberBetween(500000, 1500000000),
                'language' => fake()->languageCode(),
                'interesting_cities' => [fake()->city(), fake()->city(), fake()->city()],
                'main_religion' => fake()->randomElement(['cristianismo', 'islam', 'hinduismo', 'budismo', 'otra']),
            ],
            'politicos' => [
                'party' => fake()->company(),
                'age' => fake()->numberBetween(30, 85),
                'quotes' => [fake()->sentence(), fake()->sentence()],
                'position' => fake()->randomElement(['presidente', 'ministro', 'militante', 'diputado', 'senador', 'alcalde']),
            ],
            'musica' => [
                'artist' => fake()->name(),
                'genre' => fake()->randomElement(['pop', 'rock', 'rap', 'electronica', 'jazz']),
                'release_year' => fake()->numberBetween(1950, 2026),
            ],
            'marcas' => [
                'industry' => fake()->word(),
                'origin_country' => fake()->country(),
                'website' => fake()->url(),
            ],
            default => [
                'source' => 'proposal_seeder',
            ],
        };
    }
}

