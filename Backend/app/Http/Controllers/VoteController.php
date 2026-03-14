<?php

namespace App\Http\Controllers;

use App\Actions\Votes\DeleteVoteAction;
use App\Actions\Votes\EmitVoteAction;
use App\Actions\Votes\UpdateVoteAction;
use App\Http\Requests\StoreVoteRequest;
use App\Http\Requests\UpdateVoteRequest;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

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
        if (!$user instanceof User) {
            return response()->json(['message' => 'No se pudo obtener el usuario autenticado.'], 500);
        }

        $vote = $this->emitVoteAction->execute($user, $request->validated());
        $user->refresh();

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
		$updatedVote = $this->updateVoteAction->execute($vote, (int) $request->validated()['score']);

		return response()->json([
			'message' => 'Voto actualizado correctamente.',
			'data' => $updatedVote,
		], 200);
	}

	/**
	 * Eliminar un voto.
	 */
	public function destroy(Vote $vote): JsonResponse
	{
		Gate::authorize('delete', $vote);

        $this->deleteVoteAction->execute($vote);

		return response()->json([
			'message' => 'Voto eliminado correctamente.',
		], 200);
	}
}
