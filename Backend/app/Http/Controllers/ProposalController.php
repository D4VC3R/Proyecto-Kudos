<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Proposals\ReviewProposalAction;
use App\Http\Requests\ReviewProposalRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Proposal;
use App\Models\User;
use App\Queries\Admin\Proposals\ListAdminProposalsQuery;
use App\Queries\Admin\Proposals\ListPendingProposalsQuery;
use App\Services\ProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProposalController extends Controller
{
    public function __construct(
        protected ProposalService $proposalService,
        protected ListPendingProposalsQuery $listPendingProposalsQuery,
        protected ListAdminProposalsQuery $listAdminProposalsQuery,
        protected ReviewProposalAction $reviewProposalAction,
    ) {
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
        $pending = $this->listPendingProposalsQuery->execute($perPage);

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

    public function adminIndex(Request $request): JsonResponse
    {
        Gate::authorize('review', Proposal::class);

        $filters = [
            'status' => $request->query('status'),
            'creator_id' => $request->query('creator_id'),
            'reviewed_by' => $request->query('reviewed_by'),
            'category_id' => $request->query('category_id'),
            'search' => $request->query('search'),
        ];

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $proposals = $this->listAdminProposalsQuery->execute($filters, $perPage);

        return response()->json([
            'data' => $proposals->items(),
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'per_page' => $proposals->perPage(),
                'total' => $proposals->total(),
            ],
        ]);
    }

    public function review(ReviewProposalRequest $request, Proposal $proposal): JsonResponse
    {
        Gate::authorize('review', Proposal::class);

        $admin = $request->user();
        if (!$admin instanceof User) {
            return response()->json(['message' => 'No se pudo obtener el administrador autenticado.'], 500);
        }

        $validated = $request->validated();

        $updated = $this->reviewProposalAction->execute(
            proposal: $proposal,
            admin: $admin,
            status: $validated['status'],
            adminNotes: $validated['admin_notes'] ?? null,
        );

        return response()->json([
            'message' => 'Propuesta revisada correctamente.',
            'data' => $updated,
        ]);
    }
}
