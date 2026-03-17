<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Proposals\ReviewProposalAction;
use App\Http\Requests\DeleteProposalRequest;
use App\Http\Requests\ListAdminProposalsRequest;
use App\Http\Requests\ListPendingProposalsRequest;
use App\Http\Requests\ReviewProposalRequest;
use App\Http\Requests\ShowProposalRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Proposal;
use App\Queries\Admin\Proposals\ListAdminProposalsQuery;
use App\Queries\Admin\Proposals\ListPendingProposalsQuery;
use App\Services\ProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $proposal = $this->proposalService->createProposal(
            $request->validated(),
            $request->user()
        );

        return $this->respondMutation('Propuesta creada correctamente.', $proposal, status: 201);
    }

    public function myProposals(Request $request): JsonResponse
    {
        $proposals = $this->proposalService->getByUser($request->user());

        return $this->respondList(
            data: $proposals,
            meta: [
                'total' => $proposals->count(),
                'pending' => $proposals->where('status', Proposal::STATUS_PENDING)->count(),
                'accepted' => $proposals->where('status', Proposal::STATUS_ACCEPTED)->count(),
                'rejected' => $proposals->where('status', Proposal::STATUS_REJECTED)->count(),
                'changes_requested' => $proposals->where('status', Proposal::STATUS_CHANGES_REQUESTED)->count(),
            ],
        );
    }

    public function show(ShowProposalRequest $request, Proposal $proposal): JsonResponse
    {
        return $this->respondData($proposal->load(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name']));
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $updated = $this->proposalService->updateAndResubmit($proposal, $request->validated());

        return $this->respondMutation('Propuesta actualizada y reenviada a revisión.', $updated);
    }

    public function destroy(DeleteProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $this->proposalService->deleteProposal($proposal);

        return $this->respondMutation('Propuesta eliminada correctamente.');
    }

    public function pending(ListPendingProposalsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);
        $pending = $this->listPendingProposalsQuery->execute($perPage);

        return $this->respondList(
            data: $pending->items(),
            meta: [
                'current_page' => $pending->currentPage(),
                'last_page' => $pending->lastPage(),
                'per_page' => $pending->perPage(),
                'total' => $pending->total(),
            ],
        );
    }

    public function adminIndex(ListAdminProposalsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'status' => $validated['status'] ?? null,
            'creator_id' => $validated['creator_id'] ?? null,
            'reviewed_by' => $validated['reviewed_by'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'search' => $validated['search'] ?? null,
        ];

        $perPage = (int) ($validated['per_page'] ?? 15);
        $proposals = $this->listAdminProposalsQuery->execute($filters, $perPage);

        return $this->respondList(
            data: $proposals->items(),
            meta: [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'per_page' => $proposals->perPage(),
                'total' => $proposals->total(),
            ],
        );
    }

    public function review(ReviewProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $admin = $request->user();

        $validated = $request->validated();

        $updated = $this->reviewProposalAction->execute(
            proposal: $proposal,
            admin: $admin,
            status: $validated['status'],
            adminNotes: $validated['admin_notes'] ?? null,
        );

        return $this->respondMutation('Propuesta revisada correctamente.', $updated);
    }
}
