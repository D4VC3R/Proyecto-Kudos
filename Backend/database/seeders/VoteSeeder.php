<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VoteSeeder extends Seeder
{
	public function run(): void
	{
		// Obtener solo items aprobados
		$items = Item::where('state', Item::STATE_ACCEPTED)->get();

		if ($items->isEmpty()) {
			$this->command->error("No hay items aprobados. Ejecuta ItemSeeder primero.");
			return;
		}

		// Obtener todos los usuarios
		$users = User::all();

		if ($users->isEmpty()) {
			$this->command->error("No hay usuarios disponibles. Ejecuta UserSeeder primero.");
			return;
		}

		$votesCreated = 0;
		$votesData = [];

		foreach ($items as $item) {
			// 🎲 Número aleatorio de votos entre 5 y 20
			$numVotes = rand(5, 20);

			// Seleccionar usuarios aleatorios únicos para este item
			$voters = $users->random(min($numVotes, $users->count()));

			$itemScores = [];

			foreach ($voters as $voter) {
				// 🎲 Puntuación aleatoria entre 0 y 10
				$score = rand(0, 10);

				$itemScores[] = $score;

				$votesData[] = [
					'id' => Str::uuid(),
					'user_id' => $voter->id,
					'item_id' => $item->id,
					'score' => $score,
					'created_at' => now()->subDays(rand(1, 60)),
					'updated_at' => now(),
				];

				$votesCreated++;

				// Insertar en lotes de 500 para optimizar
				if (count($votesData) >= 500) {
					Vote::insert($votesData);
					$votesData = [];
				}
			}

			// Calcular y actualizar el vote_avg del item inmediatamente
			$voteAvg = count($itemScores) > 0 ? round(array_sum($itemScores) / count($itemScores), 2) : 0;
			$voteCount = count($itemScores);

			$item->update([
				'vote_avg' => $voteAvg,
				'vote_count' => $voteCount,
			]);
		}

		// Insertar votos restantes
		if (!empty($votesData)) {
			Vote::insert($votesData);
		}

		$this->command->newLine();
		$this->command->info("✅ {$votesCreated} votos generados correctamente.");
		$this->command->newLine();
	}
}
