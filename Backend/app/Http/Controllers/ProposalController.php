<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewProposalRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Proposal;
use App\Services\ProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProposalController extends Controller
{
	public function __construct(protected ProposalService $proposalService)
	{
	}

	public function store(StoreProposalRequest $request): JsonResponse
	{
		Gate::authorize('create', Proposal::class);

		$proposal = $this->proposalService->createProposal(
			$request->validated(),
			$request->user()
		);

		return response()->json([
			'message' => 'Propuesta creada correctamente.',
			'data' => $proposal,
		], 201);
	}

	public function myProposals(Request $request): JsonResponse
	{
		$proposals = $this->proposalService->getByUser($request->user());

		return response()->json([
			'data' => $proposals,
			'meta' => [
				'total' => $proposals->count(),
				'pending' => $proposals->where('status', Proposal::STATUS_PENDING)->count(),
				'accepted' => $proposals->where('status', Proposal::STATUS_ACCEPTED)->count(),
				'rejected' => $proposals->where('status', Proposal::STATUS_REJECTED)->count(),
				'changes_requested' => $proposals->where('status', Proposal::STATUS_CHANGES_REQUESTED)->count(),
			],
		]);
	}

	public function show(Proposal $proposal): JsonResponse
	{
		Gate::authorize('view', $proposal);

		return response()->json([
			'data' => $proposal->load(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name']),
		]);
	}

	public function update(UpdateProposalRequest $request, Proposal $proposal): JsonResponse
	{
		Gate::authorize('update', $proposal);

		$updated = $this->proposalService->updateAndResubmit($proposal, $request->validated());

		return response()->json([
			'message' => 'Propuesta actualizada y reenviada a revisión.',
			'data' => $updated,
		]);
	}

	public function destroy(Proposal $proposal): JsonResponse
	{
		Gate::authorize('delete', $proposal);

		$this->proposalService->deleteProposal($proposal);

		return response()->json([
			'message' => 'Propuesta eliminada correctamente.',
		]);
	}

	public function pending(Request $request): JsonResponse
	{
		Gate::authorize('review', Proposal::class);

		$perPage = min(max((int)$request->query('per_page', 15), 1), 100);
		$pending = $this->proposalService->getPending($perPage);

		return response()->json([
			'data' => $pending->items(),
			'meta' => [
				'current_page' => $pending->currentPage(),
				'last_page' => $pending->lastPage(),
				'per_page' => $pending->perPage(),
				'total' => $pending->total(),
			],
		]);
	}

	public function review(ReviewProposalRequest $request, Proposal $proposal): JsonResponse
	{
		Gate::authorize('review', Proposal::class);

		$validated = $request->validated();

		$updated = $this->proposalService->review(
			$proposal,
			$request->user(),
			$validated['status'],
			$validated['admin_notes'] ?? null
		);

		return response()->json([
			'message' => 'Propuesta revisada correctamente.',
			'data' => $updated,
		]);
	}
}
