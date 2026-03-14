<?php

namespace App\Queries\Admin\Items;

use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminItemsQuery
{
    /**
     * @param array{status?: ?string, category_id?: ?string, creator_id?: ?string, search?: ?string} $filters
     */
    public function execute(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Item::query()->with(['category:id,name,slug', 'creator:id,name,email', 'tags:id,name']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['creator_id'])) {
            $query->where('creator_id', $filters['creator_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'ilike', '%' . $filters['search'] . '%');
        }

        $safePerPage = min(max($perPage, 1), 100);

        return $query->orderByDesc('created_at')->paginate($safePerPage);
    }
}

