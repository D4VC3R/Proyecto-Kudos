<?php

namespace App\Http\Controllers\Vote;

use App\Actions\ChangeVoteAction;
use App\Actions\DeleteVoteAction;
use App\Actions\EmitVoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Votes\DeleteVoteRequest;
use App\Http\Requests\Votes\ListMyVotesRequest;
use App\Http\Requests\Votes\StoreVoteRequest;
use App\Http\Requests\Votes\UpdateVoteRequest;
use App\Http\Resources\VoteResource;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;

class VoteController extends Controller
{
	public function __construct(
		protected EmitVoteAction $emitVoteAction,
		protected ChangeVoteAction $changeVoteAction,
		protected DeleteVoteAction $deleteVoteAction,
	) {}

	public function myVotes(ListMyVotesRequest $request): JsonResponse
	{
		$validated = $request->validated();
		$perPage = min(max((int) ($validated['per_page'] ?? 15), 1), 100);

		$votes = Vote::query()
			->where('user_id', $request->user()->id)
			->with(['item:id,name,images,category_id,status,released_at', 'item.category:id,name,slug'])
			->applyFilters($validated)
			->latest()
			// Desempate estable: created_at tiene precisión de segundos y la paginación no debe repetir ni saltar filas (F2-05)
			->orderByDesc('id')
			->paginate($perPage);

		return $this->respondList(
			data: VoteResource::collection($votes),
			meta: [
				'current_page' => $votes->currentPage(),
				'last_page' => $votes->lastPage(),
				'per_page' => $votes->perPage(),
				'total' => $votes->total(),
			],
		);
	}

	public function store(StoreVoteRequest $request): JsonResponse
	{
		$user = $request->user();
		$validated = $request->validated();

		$vote = $this->emitVoteAction->execute($user, $validated);
		$user->refresh();

		$wasExisting = (bool) ($vote->getAttribute('was_existing') ?? false);
		$kudosAwarded = (int) ($vote->getAttribute('kudos_awarded') ?? 0); 

		return $this->respondMutation(
			message: $wasExisting
				? 'La interacción ya estaba registrada para este item.'
				: match ($vote->type) {
					Vote::TYPE_VOTE => 'Voto registrado correctamente.',
					Vote::TYPE_WANT => 'Ítem añadido a tu wishlist.',
					default => 'Item pasado correctamente.', // not_interested (y skip normalizado)
				},
			data: new VoteResource($vote),
			meta: [
				'total_kudos' => $user->total_kudos,
				'kudos_awarded' => $kudosAwarded,
				'vote_type' => $vote->type,
				'was_existing' => $wasExisting,
				'idempotent_hit' => $wasExisting,
				'reason' => $wasExisting
					? match ($vote->type) {
						Vote::TYPE_VOTE => 'already_voted',
						Vote::TYPE_WANT => 'already_wanted',
						default => 'already_skipped', // not_interested y skip: se mantiene el valor heredado (R7)
					}
					: null,
			],
			status: $wasExisting ? 200 : 201,
		);
	}

	public function update(UpdateVoteRequest $request, Vote $vote): JsonResponse
	{
		$user = $request->user();

		$updatedVote = $this->changeVoteAction->execute($vote, $request->validated());
		$user->refresh();

		return $this->respondMutation(
			message: match ($updatedVote->type) {
				Vote::TYPE_VOTE => 'Voto actualizado correctamente.',
				Vote::TYPE_WANT => 'Ítem añadido a tu wishlist.',
				default => 'Ítem marcado como "no me interesa".',
			},
			data: new VoteResource($updatedVote),
			meta: [
				'total_kudos' => $user->total_kudos,
				'kudos_awarded' => (int) ($updatedVote->getAttribute('kudos_awarded') ?? 0),
				'vote_type' => $updatedVote->type,
			],
		);
	}

	public function destroy(DeleteVoteRequest $request, Vote $vote): JsonResponse
	{
		$this->deleteVoteAction->execute($vote);

		return $this->respondMutation('Voto eliminado correctamente.');
	}
}