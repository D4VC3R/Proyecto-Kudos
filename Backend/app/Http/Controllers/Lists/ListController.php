<?php

namespace App\Http\Controllers\Lists;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserListResource;
use App\Models\UserList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Listas públicas (F2-09).
 */
class ListController extends Controller
{
    /**
     * GET /api/lists/{slug}: público. Una lista `unlisted` se ve con el enlace; una `private` da 404
     * a todos salvo a su dueño (F3-01), que la ve. Con sesión añade `liked_by_me`. Desde la F3-03 incluye `author`. Solo salen las
     * entradas con ítem activo de una categoría activa.
     */
    public function show(Request $request, UserList $list): JsonResponse
    {
        $viewer = $request->user();

        // Privada: solo la ve su dueño (F3-01); a los demás les da 404, como si no existiera.
        if (! Gate::forUser($viewer)->allows('view', $list)) {
            throw (new ModelNotFoundException)->setModel(UserList::class, [$list->getKey()]);
        }

        $list->setRelation('listItems', $list->visibleListItems()->with([
            'item' => fn ($query) => $query->with('category')
                ->when($viewer, fn ($q) => $q->with(['userVote' => fn ($vote) => $vote->where('user_id', $viewer->id)])),
        ])->get());

        if ($viewer) {
            $list->loadExists(['likes as liked_by_me' => fn ($q) => $q->where('users.id', $viewer->id)]);
        }

        $list->loadMissing(['user:id,name,username', 'user.profile:id,user_id,avatar,is_public']);

        return $this->respondData(new UserListResource($list));
    }
}
