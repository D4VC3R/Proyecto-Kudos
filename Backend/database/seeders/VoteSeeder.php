<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoteSeeder extends Seeder
{
	public function run(): void
	{
		$itemIds = Item::where('status', Item::STATUS_ACTIVE)->pluck('id');

		if ($itemIds->isEmpty()) {
			$this->command->error('No hay items aprobados. Ejecuta ItemSeeder primero.');
			return;
		}

		$userIds = User::pluck('id');

		if ($userIds->isEmpty()) {
			$this->command->error('No hay usuarios disponibles. Ejecuta UserSeeder primero.');
			return;
		}

		$now = now();
		$voteRows = [];
		$kudosRows = [];
		$kudosByUser = [];

		$reward = (int) config('kudos.rewards.vote_first_time_item');
		$reason = (string) config('kudos.reasons.vote_first_time_item');

		foreach ($itemIds as $itemId) {
			$numVotes = random_int(5, 20);
			$voters = $userIds->random(min($numVotes, $userIds->count()));

			foreach ($voters as $voterId) {
				$createdAt = Carbon::instance($now)->subDays(random_int(1, 60));

				$voteRows[] = [
					'id' => Str::uuid()->toString(),
					'type' => Vote::TYPE_VOTE,
					'score' => random_int(0, 10),
					'user_id' => $voterId,
					'item_id' => $itemId,
					'created_at' => $createdAt->toDateTimeString(),
					'updated_at' => $createdAt->toDateTimeString(),
				];

				$kudosRows[] = [
					'id' => Str::uuid()->toString(),
					'user_id' => $voterId,
					'kudos_amount' => $reward,
					'reason' => $reason,
					// Replicamos la lógica del buildActionKey del KudosService (implode con ':')[cite: 49]
					'action_key' => "{$reason}:{$voterId}:{$itemId}",
					'reference_type' => Item::class,
					'reference_id' => $itemId,
					'created_at' => $createdAt->toDateTimeString(),
					'updated_at' => $createdAt->toDateTimeString(),
				];

				$kudosByUser[$voterId] = ($kudosByUser[$voterId] ?? 0) + $reward;
			}
		}

		DB::transaction(function () use ($voteRows, $kudosRows, $kudosByUser) {
			foreach (array_chunk($voteRows, 1000) as $chunk) {
				Vote::insert($chunk);
			}

			foreach (array_chunk($kudosRows, 1000) as $chunk) {
				DB::table('kudos_transactions')->insert($chunk);
			}

			foreach ($kudosByUser as $userId => $kudosAmount) {
				User::where('id', $userId)->increment('total_kudos', $kudosAmount);
			}
		});

		$voteStats = Vote::query()
			->where('type', Vote::TYPE_VOTE)
			->selectRaw('item_id, AVG(score) as avg_score, COUNT(*) as total_votes')
			->groupBy('item_id')
			->get();

		DB::transaction(function () use ($voteStats) {
			foreach ($voteStats as $stat) {
				Item::where('id', $stat->item_id)->update([
					'vote_avg' => round((float) $stat->avg_score, 2),
					'vote_count' => (int) $stat->total_votes,
				]);
			}
		});

		$this->command->info(count($voteRows) . ' votos generados.');
	}
}
