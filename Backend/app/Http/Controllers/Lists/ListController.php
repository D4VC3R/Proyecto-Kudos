<?php

namespace App\Http\Controllers\Lists;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserListResource;
use App\Models\UserList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Listas públicas (F2-09).
 */
class ListController extends Controller
{
    /**
     * GET /api/lists/{slug}: público. Una lista `unlisted` se ve con el enlace; una `private` da 404
     * (en la F3-01 podrá verla su dueño). Solo salen las entradas con ítem activo de una categoría activa.
     */
    public function show(Request $request, UserList $list): JsonResponse
    {
        if ($list->visibility === UserList::VISIBILITY_PRIVATE) {
            throw (new ModelNotFoundException)->setModel(UserList::class, [$list->getKey()]);
        }

        $viewer = $request->user('sanctum');

        $list->setRelation('listItems', $list->visibleListItems()->with([
            'item' => fn ($query) => $query->with('category')
                ->when($viewer, fn ($q) => $q->with(['userVote' => fn ($vote) => $vote->where('user_id', $viewer->id)])),
        ])->get());

        return $this->respondData(new UserListResource($list));
    }
}
