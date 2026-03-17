<?php

namespace App\Http\Controllers;

use App\Actions\Votes\DeleteVoteAction;
use App\Actions\Votes\EmitVoteAction;
use App\Actions\Votes\UpdateVoteAction;
use App\Http\Requests\DeleteVoteRequest;
use App\Http\Requests\StoreVoteRequest;
use App\Http\Requests\UpdateVoteRequest;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;

class VoteController extends Controller
{
    public function __construct(
        protected EmitVoteAction $emitVoteAction,
        protected UpdateVoteAction $updateVoteAction,
        protected DeleteVoteAction $deleteVoteAction,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
	public function store(StoreVoteRequest $request): JsonResponse
	{
		$user = $request->user();
				if (!$user) {
					return response()->json(['message' => 'No se pudo obtener el usuario autenticado.'], 500);
				}

		$validated = $request->validated();
		$vote = $this->emitVoteAction->execute($user, $validated);
        $user->refresh();

		$isSkip = ($validated['type'] ?? Vote::TYPE_VOTE) === Vote::TYPE_SKIP;
		$wasExisting = (bool) ($vote->getAttribute('was_existing') ?? false);
		$statusCode = $wasExisting ? 200 : 201;

		return response()->json([
	  'message' => $wasExisting
			? ($isSkip ? 'Interacción actualizada a skip correctamente.' : 'Voto actualizado correctamente.')
			: ($isSkip ? 'Item pasado correctamente.' : 'Voto registrado correctamente.'),
			'data' => $vote,
            'meta' => [
                'total_kudos' => $user->total_kudos,
				'vote_type' => $vote->type,
				'was_existing' => $wasExisting,
            ],
		], $statusCode);
	}

	/**
	 * Actualizar un voto existente.
	 */
	public function update(UpdateVoteRequest $request, Vote $vote): JsonResponse
	{
		$payload = $request->validated();
		$updatedVote = $this->updateVoteAction->execute($vote, $payload);

		return response()->json([
			'message' => $updatedVote->type === Vote::TYPE_SKIP
				? 'Interacción actualizada a skip correctamente.'
				: 'Voto actualizado correctamente.',
			'data' => $updatedVote,
		], 200);
	}

	/**
	 * Eliminar un voto.
	 */
	public function destroy(DeleteVoteRequest $request, Vote $vote): JsonResponse
	{

        $this->deleteVoteAction->execute($vote);

		return response()->json([
			'message' => 'Voto eliminado correctamente.',
		], 200);
	}
}
