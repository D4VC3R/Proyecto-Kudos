<?php

namespace App\Http\Controllers\Lists;

use App\Actions\LikeListAction;
use App\Actions\UnlikeListAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\LikeListRequest;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * "Me gusta" de listas (F3-01). Ambas operaciones son idempotentes.
 */
class ListLikeController extends Controller
{
    public function __construct(
        private readonly LikeListAction $like,
        private readonly UnlikeListAction $unlike,
    ) {}

    /**
     * POST /api/lists/{list}/like: da "me gusta". Privada ajena: 404; lista propia: 403.
     */
    public function store(LikeListRequest $request, UserList $list): JsonResponse
    {
        $this->authorizeLike($request->user(), $list);

        $result = $this->like->execute($list, $request->user());

        return $this->respondMutation('Te gusta esta lista.', $result);
    }

    /**
     * DELETE /api/lists/{list}/like: quita el "me gusta".
     */
    public function destroy(LikeListRequest $request, UserList $list): JsonResponse
    {
        $this->authorizeLike($request->user(), $list);

        $result = $this->unlike->execute($list, $request->user());

        return $this->respondMutation('Ya no te gusta esta lista.', $result);
    }

    /**
     * Una privada ajena no se delata (404); a la propia no se le da "me gusta" (403).
     */
    private function authorizeLike(User $user, UserList $list): void
    {
        if (! Gate::forUser($user)->allows('view', $list)) {
            throw (new ModelNotFoundException)->setModel(UserList::class, [$list->getKey()]);
        }

        if (! Gate::forUser($user)->allows('like', $list)) {
            throw new AuthorizationException('No puedes dar me gusta a tu propia lista.');
        }
    }
}
