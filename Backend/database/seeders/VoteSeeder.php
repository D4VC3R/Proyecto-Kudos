<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Services\KudosRules;
use App\Services\KudosService;
use Illuminate\Database\Seeder;

class VoteSeeder extends Seeder
{
    public function run(): void
    {
        $kudosService = app(KudosService::class);

        $items = Item::where('status', Item::STATUS_ACTIVE)->get();

        if ($items->isEmpty()) {
            $this->command->error('No hay items aprobados. Ejecuta ItemSeeder primero.');
            return;
        }

        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->error('No hay usuarios disponibles. Ejecuta UserSeeder primero.');
            return;
        }

        $votesCreated = 0;

        foreach ($items as $item) {
            $numVotes = rand(5, 20);
            $voters = $users->random(min($numVotes, $users->count()));

            foreach ($voters as $voter) {
                $vote = Vote::factory()
                    ->forItem($item)
                    ->byUser($voter)
                    ->create([
                        'created_at' => now()->subDays(rand(1, 60)),
                    ]);

                $kudosService->awardIfFirst(
                    user: $voter,
                    kudosAmount: KudosRules::rewardForVoteFirstTimeItem(),
                    reason: KudosRules::reasonForVoteFirstTimeItem(),
                    actionKey: KudosRules::actionKeyForVoteFirstTimeItem($voter->id, $item->id),
                    referenceType: Item::class,
                    referenceId: $item->id,
                );

                $votesCreated++;
            }

            $item->update([
                'vote_avg' => round($item->votes()->avg('score'), 2),
                'vote_count' => $item->votes()->count(),
            ]);
        }

        $this->command->newLine();
        $this->command->info("{$votesCreated} votos generados.");
        $this->command->newLine();
    }
}
