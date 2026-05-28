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
			->with(['item:id,name,images,category_id,status', 'item.category:id,name,slug'])
			->applyFilters($validated)
			->latest()
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

		$isSkip = ($validated['type'] ?? Vote::TYPE_VOTE) === Vote::TYPE_SKIP;
		$wasExisting = (bool) ($vote->getAttribute('was_existing') ?? false);
		$kudosAwarded = (int) ($vote->getAttribute('kudos_awarded') ?? 0); 

		return $this->respondMutation(
			message: $wasExisting
				? 'La interacción ya estaba registrada para este item.'
				: ($isSkip ? 'Item pasado correctamente.' : 'Voto registrado correctamente.'),
			data: new VoteResource($vote),
			meta: [
				'total_kudos' => $user->total_kudos,
				'kudos_awarded' => $kudosAwarded,
				'vote_type' => $vote->type,
				'was_existing' => $wasExisting,
				'idempotent_hit' => $wasExisting,
				'reason' => $wasExisting ? ($vote->type === Vote::TYPE_SKIP ? 'already_skipped' : 'already_voted') : null,
			],
			status: $wasExisting ? 200 : 201,
		);
	}

	public function update(UpdateVoteRequest $request, Vote $vote): JsonResponse
	{
		$updatedVote = $this->changeVoteAction->execute($vote, $request->validated());

		return $this->respondMutation(
			message: $updatedVote->type === Vote::TYPE_SKIP
				? 'Interacción actualizada a skip correctamente.'
				: 'Voto actualizado correctamente.',
			data: new VoteResource($updatedVote),
		);
	}

	public function destroy(DeleteVoteRequest $request, Vote $vote): JsonResponse
	{
		$this->deleteVoteAction->execute($vote);

		return $this->respondMutation('Voto eliminado correctamente.');
	}
}