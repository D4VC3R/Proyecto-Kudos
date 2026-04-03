<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use App\Services\KudosRules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoteSeeder extends Seeder
{
    public function run(): void
    {
        $items = Item::query()
            ->where('status', Item::STATUS_ACTIVE)
            ->get(['id']);

        if ($items->isEmpty()) {
            $this->command->error('No hay items aprobados. Ejecuta ItemSeeder primero.');
            return;
        }

        $users = User::query()->get(['id']);

        if ($users->isEmpty()) {
            $this->command->error('No hay usuarios disponibles. Ejecuta UserSeeder primero.');
            return;
        }

        $now = now();
        $voteRows = [];
        $kudosRows = [];
        $kudosByUser = [];
        $reward = KudosRules::rewardForVoteFirstTimeItem();
        $reason = KudosRules::reasonForVoteFirstTimeItem();
        $userIds = $users->pluck('id')->all();

        foreach ($items as $item) {
            $numVotes = random_int(5, 20);
            $voters = $this->pickRandomVoters($userIds, min($numVotes, count($userIds)));

            foreach ($voters as $voterId) {
                $createdAt = Carbon::instance($now)->subDays(random_int(1, 60));

                $voteRows[] = [
                    'id' => (string) Str::uuid(),
                    'type' => Vote::TYPE_VOTE,
                    'score' => random_int(0, 10),
                    'user_id' => $voterId,
                    'item_id' => $item->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $kudosRows[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $voterId,
                    'kudos_amount' => $reward,
                    'reason' => $reason,
                    'action_key' => KudosRules::actionKeyForVoteFirstTimeItem($voterId, $item->id),
                    'reference_type' => Item::class,
                    'reference_id' => $item->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $kudosByUser[$voterId] = ($kudosByUser[$voterId] ?? 0) + $reward;
            }
        }

        DB::transaction(function () use ($voteRows, $kudosRows, $kudosByUser) {
            foreach (array_chunk($voteRows, 2000) as $chunk) {
                DB::table('votes')->insert($chunk);
            }

            foreach (array_chunk($kudosRows, 2000) as $chunk) {
                DB::table('kudos_transactions')->insert($chunk);
            }

            foreach ($kudosByUser as $userId => $kudosAmount) {
                DB::table('users')->where('id', $userId)->increment('total_kudos', $kudosAmount);
            }
        });

        $voteStats = DB::table('votes')
            ->where('type', Vote::TYPE_VOTE)
            ->selectRaw('item_id, AVG(score) as avg_score, COUNT(*) as total_votes')
            ->groupBy('item_id')
            ->get();

        foreach ($voteStats as $stat) {
            DB::table('items')
                ->where('id', $stat->item_id)
                ->update([
                    'vote_avg' => round((float) $stat->avg_score, 2),
                    'vote_count' => (int) $stat->total_votes,
                    'updated_at' => now(),
                ]);
        }

        $this->command->newLine();
        $this->command->info(count($voteRows) . ' votos generados.');
        $this->command->newLine();
    }

    /**
     * @param array<int,string> $userIds
     * @return array<int,string>
     */
    private function pickRandomVoters(array $userIds, int $count): array
    {
        shuffle($userIds);

        return array_slice($userIds, 0, $count);
    }
}
