<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;

class VoteSeeder extends Seeder
{
    public function run(): void
    {
        $items = Item::where('state', Item::STATE_ACCEPTED)->get();

        if ($items->isEmpty()) {
            $this->command->error("No hay items aprobados. Ejecuta ItemSeeder primero.");
            return;
        }

        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->error("No hay usuarios disponibles. Ejecuta UserSeeder primero.");
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

                // ✅ Registrar kudos por la votación
                $vote->kudosTransactions()->create([
                    'user_id' => $voter->id,
                    'kudos_amount' => 5,
                    'reason' => 'item_voted',
                ]);

                // ✅ Actualizar total_kudos del votante
                $voter->increment('total_kudos', 5);

                $votesCreated++;
            }

            // Recalcular estadísticas del item
            $item->update([
                'vote_avg' => round($item->votes()->avg('score'), 2),
                'vote_count' => $item->votes()->count(),
            ]);
        }

        $this->command->newLine();
        $this->command->info("{$votesCreated} votos generados con kudos registrados.");
        $this->command->newLine();
    }
}
