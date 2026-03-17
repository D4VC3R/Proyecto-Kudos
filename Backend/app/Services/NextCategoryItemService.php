<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NextCategoryItemService
{
    private const CACHE_TTL_SECONDS = 21600;

    /**
     * @return array{item: Item, remaining: int}|null
     */
    public function getNextItem(User $user, Category $category): ?array
    {
        $eligibleItemIds = Item::query()
            ->where('category_id', $category->id)
            ->where('status', Item::STATUS_ACTIVE)
            ->whereDoesntHave('votes', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id')
            ->all();

        if (empty($eligibleItemIds)) {
            Cache::forget($this->cacheKey($user->id, $category->id));
            return null;
        }

        sort($eligibleItemIds);
        $signature = sha1(implode('|', $eligibleItemIds));
        $cacheKey = $this->cacheKey($user->id, $category->id);

        $state = Cache::get($cacheKey);

        if (!$this->isValidState($state, $signature)) {
            $state = $this->buildState($eligibleItemIds, $signature);
        }

        $order = $state['order'];
        $cursor = (int) $state['cursor'];

        if ($cursor >= count($order)) {
            return null;
        }

        $nextItemId = $order[$cursor];
        $state['cursor'] = $cursor + 1;

        Cache::put($cacheKey, $state, now()->addSeconds(self::CACHE_TTL_SECONDS));

        $item = Item::query()
            ->with(['category', 'creator', 'tags'])
            ->find($nextItemId);

        if (!$item) {
            Cache::forget($cacheKey);
            return null;
        }

        return [
            'item' => $item,
            'remaining' => max(0, count($order) - (int) $state['cursor']),
        ];
    }

    private function cacheKey(string $userId, string $categoryId): string
    {
        return "next-item-queue:{$userId}:{$categoryId}";
    }

    /**
     * @param array<string,mixed>|mixed $state
     */
    private function isValidState($state, string $signature): bool
    {
        if (!is_array($state)) {
            return false;
        }

        if (!isset($state['signature'], $state['order'], $state['cursor'])) {
            return false;
        }

        return $state['signature'] === $signature && is_array($state['order']);
    }

    /**
     * @param array<int,string> $eligibleItemIds
     * @return array{signature:string,order:array<int,string>,cursor:int}
     */
    private function buildState(array $eligibleItemIds, string $signature): array
    {
        shuffle($eligibleItemIds);

        return [
            'signature' => $signature,
            'order' => array_values($eligibleItemIds),
            'cursor' => 0,
        ];
    }
}

