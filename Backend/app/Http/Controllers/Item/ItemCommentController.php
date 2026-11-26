<?php

namespace App\Http\Controllers\Item;

use App\Actions\CreateItemCommentAction;
use App\Actions\ModerateCommentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Items\DeleteItemCommentRequest;
use App\Http\Requests\Items\HideItemCommentRequest;
use App\Http\Requests\Items\ListItemCommentsRequest;
use App\Http\Requests\Items\StoreItemCommentRequest;
use App\Http\Requests\Items\UnhideItemCommentRequest;
use App\Http\Requests\Items\UpdateItemCommentRequest;
use App\Http\Resources\ItemCommentResource;
use App\Models\Item;
use App\Models\ItemComment;
use Illuminate\Http\JsonResponse;

class ItemCommentController extends Controller
{
    public function index(ListItemCommentsRequest $request, Item $item): JsonResponse
    {
        $validated = $request->validated();
        $perPage = min(max((int) ($validated['per_page'] ?? 15), 1), 100);

        $user = $request->user();
        $includeHidden = $user?->hasRole('admin') ?? false;

        // Los ocultos solo los ven el admin y su autor (en los dos niveles).
        $visible = function ($query) use ($includeHidden, $user) {
            if ($includeHidden) {
                return;
            }
            $query->where(function ($q) use ($user) {
                $q->where('is_hidden', false);
                if ($user) {
                    $q->orWhere('user_id', $user->id);
                }
            });
        };

        // F3-02: solo comentarios principales; primero las reseñas (con su nota) y dentro de cada grupo los más recientes.
        // Las respuestas van anidadas, de la más antigua a la más reciente.
        $comments = ItemComment::query()
            ->where('item_id', $item->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name',
                'user.profile:id,user_id,avatar',
                'vote:id,type,score',
                'replies' => function ($query) use ($visible) {
                    $visible($query);
                    $query->with(['user:id,name', 'user.profile:id,user_id,avatar'])->oldest()->orderBy('id');
                },
            ])
            ->tap($visible)
            ->orderByRaw('vote_id IS NULL')
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->respondList(
            data: ItemCommentResource::collection($comments),
            meta: [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
            links: [
                'first' => $comments->url(1),
                'last' => $comments->url($comments->lastPage()),
                'prev' => $comments->previousPageUrl(),
                'next' => $comments->nextPageUrl(),
            ],
        );
    }

    public function store(StoreItemCommentRequest $request, Item $item, CreateItemCommentAction $createComment): JsonResponse
    {
        $payload = $request->validated();

        $comment = $createComment->execute(
            item: $item,
            author: $request->user(),
            content: $payload['content'],
            parent: $request->parentComment(),
            reviewVote: $request->reviewVote(),
        )->load(['user:id,name', 'user.profile:id,user_id,avatar', 'vote:id,type,score']);

        return $this->respondMutation(
            message: 'Comentario registrado correctamente.',
            data: new ItemCommentResource($comment),
            status: 201,
        );
    }

    public function update(UpdateItemCommentRequest $request, ItemComment $comment): JsonResponse
    {
        $comment->update($request->validated());

        return $this->respondMutation(
            message: 'Comentario actualizado correctamente.',
            data: new ItemCommentResource($comment->fresh(['user:id,name', 'user.profile:id,user_id,avatar'])),
        );
    }

    public function destroy(DeleteItemCommentRequest $request, ItemComment $comment, ModerateCommentAction $moderateComment): JsonResponse
    {
        $user = $request->user();

        // Solo el borrado de moderación avisa al autor y queda registrado; el del propio autor no.
        if ($user->hasRole('admin')) {
            $moderateComment->execute($comment, $user, ModerateCommentAction::ACTION_DELETE);
        } else {
            $comment->delete();
        }

        return $this->respondMutation('Comentario eliminado correctamente.');
    }

    public function hide(HideItemCommentRequest $request, ItemComment $comment, ModerateCommentAction $moderateComment): JsonResponse
    {
        $moderateComment->execute(
            $comment,
            $request->user(),
            ModerateCommentAction::ACTION_HIDE,
            $request->validated()['reason'] ?? null,
        );

        return $this->respondMutation(
            message: 'Comentario ocultado correctamente.',
            data: new ItemCommentResource($comment->fresh(['user:id,name', 'user.profile:id,user_id,avatar'])),
        );
    }

    public function unhide(UnhideItemCommentRequest $request, ItemComment $comment, ModerateCommentAction $moderateComment): JsonResponse
    {
        $moderateComment->execute($comment, $request->user(), ModerateCommentAction::ACTION_UNHIDE);

        return $this->respondMutation(
            message: 'Comentario restaurado correctamente.',
            data: new ItemCommentResource($comment->fresh(['user:id,name', 'user.profile:id,user_id,avatar'])),
        );
    }
}
