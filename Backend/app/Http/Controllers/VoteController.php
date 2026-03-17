<?php

namespace App\Http\Controllers;

use App\Actions\Votes\DeleteVoteAction;
use App\Actions\Votes\EmitVoteAction;
use App\Actions\Votes\UpdateVoteAction;
use App\Http\Requests\DeleteVoteRequest;
use App\Http\Requests\ListMyVotesRequest;
use App\Http\Requests\StoreVoteRequest;
use App\Http\Requests\UpdateVoteRequest;
use App\Http\Resources\VoteResource;
use App\Models\Vote;
use App\Queries\Votes\ListMyVotesQuery;
use Illuminate\Http\JsonResponse;

class VoteController extends Controller
{
    public function __construct(
        protected EmitVoteAction $emitVoteAction,
        protected UpdateVoteAction $updateVoteAction,
        protected DeleteVoteAction $deleteVoteAction,
		protected ListMyVotesQuery $listMyVotesQuery,
    ) {
    }

	public function myVotes(ListMyVotesRequest $request): JsonResponse
	{
		$user = $request->user();

		$validated = $request->validated();
		$filters = [
			'type' => $validated['type'] ?? null,
			'category_id' => $validated['category_id'] ?? null,
			'search' => $validated['search'] ?? null,
		];

		$votes = $this->listMyVotesQuery->execute(
			user: $user,
			filters: $filters,
			perPage: (int) ($validated['per_page'] ?? 15),
		);

		return $this->respondList(
			data: VoteResource::collection($votes),
			meta: [
				'current_page' => $votes->currentPage(),
				'last_page' => $votes->lastPage(),
				'per_page' => $votes->perPage(),
				'total' => $votes->total(),
			],
			links: [
				'first' => $votes->url(1),
				'last' => $votes->url($votes->lastPage()),
				'prev' => $votes->previousPageUrl(),
				'next' => $votes->nextPageUrl(),
			],
		);
	}

    /**
     * Display a listing of the resource.
     */
	public function store(StoreVoteRequest $request): JsonResponse
	{
		$user = $request->user();
				if (!$user) {
					return $this->respondMutation('No se pudo obtener el usuario autenticado.', status: 500);
				}

		$validated = $request->validated();
		$vote = $this->emitVoteAction->execute($user, $validated);
        $user->refresh();

		$isSkip = ($validated['type'] ?? Vote::TYPE_VOTE) === Vote::TYPE_SKIP;
		$wasExisting = (bool) ($vote->getAttribute('was_existing') ?? false);
		$statusCode = $wasExisting ? 200 : 201;
		$reason = null;
		if ($wasExisting) {
			$reason = $vote->type === Vote::TYPE_SKIP ? 'already_skipped' : 'already_voted';
		}

		return $this->respondMutation(
			message: $wasExisting
				? 'La interacción ya estaba registrada para este item.'
				: ($isSkip ? 'Item pasado correctamente.' : 'Voto registrado correctamente.'),
			data: $vote,
			meta: [
				'total_kudos' => $user->total_kudos,
				'vote_type' => $vote->type,
				'was_existing' => $wasExisting,
				'idempotent_hit' => $wasExisting,
				'reason' => $reason,
			],
			status: $statusCode,
		);
	}

	/**
	 * Actualizar un voto existente.
	 */
	public function update(UpdateVoteRequest $request, Vote $vote): JsonResponse
	{
		$updatedVote = $this->updateVoteAction->execute($vote, (int) $request->validated()['score']);

		return $this->respondMutation(
			message: $updatedVote->type === Vote::TYPE_SKIP
				? 'Interacción actualizada a skip correctamente.'
				: 'Voto actualizado correctamente.',
			data: $updatedVote,
		);
	}

	/**
	 * Eliminar un voto.
	 */
	public function destroy(DeleteVoteRequest $request, Vote $vote): JsonResponse
	{

        $this->deleteVoteAction->execute($vote);

		return $this->respondMutation('Voto eliminado correctamente.');
	}
}
