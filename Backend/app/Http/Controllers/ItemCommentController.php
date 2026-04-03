<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteItemCommentRequest;
use App\Http\Requests\HideItemCommentRequest;
use App\Http\Requests\ListItemCommentsRequest;
use App\Http\Requests\StoreItemCommentRequest;
use App\Http\Requests\UnhideItemCommentRequest;
use App\Http\Requests\UpdateItemCommentRequest;
use App\Http\Resources\ItemCommentResource;
use App\Models\Item;
use App\Models\ItemComment;
use App\Services\ItemCommentService;
use Illuminate\Http\JsonResponse;

class ItemCommentController extends Controller
{
    public function __construct(protected ItemCommentService $itemCommentService)
    {
    }

    public function index(ListItemCommentsRequest $request, Item $item): JsonResponse
    {
        $validated = $request->validated();
        $comments = $this->itemCommentService->listForItem(
            item: $item,
            viewer: $request->user(),
            perPage: (int) ($validated['per_page'] ?? 15),
        );

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

    public function store(StoreItemCommentRequest $request, Item $item): JsonResponse
    {
        $comment = $this->itemCommentService->create(
            user: $request->user(),
            item: $item,
            payload: $request->validated(),
        );

        return $this->respondMutation(
            message: 'Comentario registrado correctamente.',
            data: new ItemCommentResource($comment),
            status: 201,
        );
    }

    public function update(UpdateItemCommentRequest $request, ItemComment $comment): JsonResponse
    {
        $updated = $this->itemCommentService->update($comment, $request->validated());

        return $this->respondMutation(
            message: 'Comentario actualizado correctamente.',
            data: new ItemCommentResource($updated),
        );
    }

    public function destroy(DeleteItemCommentRequest $request, ItemComment $comment): JsonResponse
    {
        $this->itemCommentService->delete($comment);

        return $this->respondMutation('Comentario eliminado correctamente.');
    }

    public function hide(HideItemCommentRequest $request, ItemComment $comment): JsonResponse
    {
        $hidden = $this->itemCommentService->hide(
            comment: $comment,
            admin: $request->user(),
            reason: $request->validated()['reason'] ?? null,
        );

        return $this->respondMutation(
            message: 'Comentario ocultado correctamente.',
            data: new ItemCommentResource($hidden),
        );
    }

    public function unhide(UnhideItemCommentRequest $request, ItemComment $comment): JsonResponse
    {
        $visible = $this->itemCommentService->unhide($comment);

        return $this->respondMutation(
            message: 'Comentario restaurado correctamente.',
            data: new ItemCommentResource($visible),
        );
    }
}

