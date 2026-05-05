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
use Illuminate\Http\JsonResponse;

class ItemCommentController extends Controller
{
	public function index(ListItemCommentsRequest $request, Item $item): JsonResponse
	{
		$validated = $request->validated();
		$perPage = min(max((int) ($validated['per_page'] ?? 15), 1), 100);

		$user = $request->user();
		$includeHidden = $user?->hasRole('admin') ?? false;

		$comments = ItemComment::query()
			->where('item_id', $item->id)
			->with(['user:id,name'])
			->when(!$includeHidden, fn($q) => $q->where('is_hidden', false))
			->latest()
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

	public function store(StoreItemCommentRequest $request, Item $item): JsonResponse
	{
		$payload = $request->validated();

		$comment = ItemComment::create([
			'item_id' => $item->id,
			'user_id' => $request->user()->id,
			'content' => $payload['content'],
		])->load(['user:id,name']);

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
			data: new ItemCommentResource($comment->fresh(['user:id,name'])),
		);
	}

	public function destroy(DeleteItemCommentRequest $request, ItemComment $comment): JsonResponse
	{
		$comment->delete();

		return $this->respondMutation('Comentario eliminado correctamente.');
	}

	public function hide(HideItemCommentRequest $request, ItemComment $comment): JsonResponse
	{
		$comment->update([
			'is_hidden' => true,
			'hidden_reason' => $request->validated()['reason'] ?? null,
			'hidden_by' => $request->user()->id,
		]);

		return $this->respondMutation(
			message: 'Comentario ocultado correctamente.',
			data: new ItemCommentResource($comment->fresh(['user:id,name'])),
		);
	}

	public function unhide(UnhideItemCommentRequest $request, ItemComment $comment): JsonResponse
	{
		$comment->update([
			'is_hidden' => false,
			'hidden_reason' => null,
			'hidden_by' => null,
		]);

		return $this->respondMutation(
			message: 'Comentario restaurado correctamente.',
			data: new ItemCommentResource($comment->fresh(['user:id,name'])),
		);
	}
}