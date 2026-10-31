<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Servicio que elige los ítems del speed-rating del onboarding (F2-07).
 */
class OnboardingItemsService
{
    /**
     * Ítems para el speed-rating del onboarding (F2-07): los más populares (popularity desc, id como desempate)
     * de cada categoría activa elegida, activos, descubribles (quality_score suficiente, F0-05), sin borrar y
     * sin voto de ningún tipo del usuario. Se reparten por turnos entre las categorías (orden sort_order, name):
     * el 1.º de cada una, después el 2.º… Si una categoría se queda sin ítems, las demás completan el cupo.
     *
     * @param User|null $user Usuario que hace el onboarding, o null (portada sin sesión, F2-09): sin usuario no se excluye nada.
     * @param array<int, string> $categorySlugs Slugs de las categorías elegidas.
     * @param int|null $limit Máximo de ítems (por defecto catalog.onboarding_items).
     * @return Collection<int, Item>
     */
    public function popularItems(?User $user, array $categorySlugs, ?int $limit = null): Collection
    {
        $limit ??= (int) config('catalog.onboarding_items', 10);

        if ($limit <= 0) {
            return collect();
        }

        $categories = Category::query()
            ->whereIn('slug', $categorySlugs)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id']);

        if ($categories->isEmpty()) {
            return collect();
        }

        // Una consulta por categoría (son pocas). userVote cargado para que ItemListResource dé can_vote = true.
        $perCategory = $categories->map(fn (Category $category) => Item::query()
            ->where('category_id', $category->id)
            ->where('status', Item::STATUS_ACTIVE)
            ->discoverable()
            ->when($user, fn ($query) => $query->whereDoesntHave('votes', fn ($votes) => $votes->where('user_id', $user->id)))
            ->orderByDesc('popularity')
            ->orderBy('id')
            ->limit($limit)
            ->with(['category'])
            ->when($user, fn ($query) => $query->with(['userVote' => fn ($vote) => $vote->where('user_id', $user->id)]))
            ->get()
            ->values());

        // Reparto por turnos: el 1.º de cada categoría, después el 2.º…
        $result = collect();

        for ($round = 0; $result->count() < $limit; $round++) {
            $added = false;

            foreach ($perCategory as $items) {
                if ($result->count() >= $limit) {
                    break;
                }

                if (isset($items[$round])) {
                    $result->push($items[$round]);
                    $added = true;
                }
            }

            if (! $added) {
                break;
            }
        }

        return $result->values();
    }
}
