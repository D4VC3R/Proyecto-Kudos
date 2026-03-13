<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoteRequest;
use App\Http\Requests\UpdateVoteRequest;
use App\Models\Item;
use App\Models\Vote;
use App\Services\VoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class VoteController extends Controller
{
	protected VoteService $voteService;

	public function __construct(VoteService $voteService){
		$this->voteService = $voteService;
	}
    /**
     * Display a listing of the resource.
     */
	public function store(StoreVoteRequest $request): JsonResponse
	{
		// 1. Extraemos usuario y datos validados
        $user = $request->user();
		$validatedData = $request->validated();

		$item = Item::findOrFail($validatedData['item_id']);
        Gate::authorize('create', [Vote::class, $item]);

		// 2. Pasamos la pelota al Servicio
		$vote = $this->voteService->emitVote($user, $validatedData);

		return response()->json([
			'message' => 'Voto registrado correctamente.',
			'data' => $vote,
            'meta' => [
                'total_kudos' => $user->total_kudos,
            ],
		], 201); // 201 Created
	}

	/**
	 * Actualizar un voto existente.
	 */
	public function update(UpdateVoteRequest $request, Vote $vote): JsonResponse
	{
		Gate::authorize('update', $vote);

		$validatedData = $request->validated();

		$updatedVote = $this->voteService->changeVote($vote, $validatedData['score']);

		return response()->json([
			'message' => 'Voto actualizado correctamente.',
			'data' => $updatedVote
		], 200);
	}

	/**
	 * Eliminar un voto.
	 */
	public function destroy(Vote $vote): JsonResponse
	{
		Gate::authorize('delete', $vote);

        $this->voteService->deleteVote($vote);

		return response()->json([
			'message' => 'Voto eliminado correctamente.'
		], 200);
	}
}
