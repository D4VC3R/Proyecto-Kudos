<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\ListAdminProposalsRequest;
use App\Http\Requests\Admin\ReviewProposalRequest;
use App\Http\Requests\Admin\StoreProposalRequest;
use App\Http\Requests\ListPendingProposalsRequest;
use App\Http\Requests\Proposals\DeleteProposalRequest;
use App\Http\Requests\Proposals\ShowProposalRequest;
use App\Http\Requests\Proposals\UpdateProposalRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use App\Services\AdminService;
use App\Services\ProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function __construct(
        protected AdminService $adminService,
        protected ProposalService $proposalService,
    ) {
    }

    public function store(StoreProposalRequest $request): JsonResponse
    {
        $proposal = $this->proposalService->createProposal(
            $request->validated(),
            $request->user()
        );

        return $this->respondMutation('Propuesta creada correctamente.', new ProposalResource($proposal), status: 201);
    }

    public function myProposals(Request $request): JsonResponse
    {
        $proposals = $this->proposalService->getByUser($request->user());

        return $this->respondList(
            data: ProposalResource::collection($proposals),
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
        return $this->respondData(
            new ProposalResource($proposal->load(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name']))
        );
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $updated = $this->proposalService->updateAndResubmit($proposal, $request->validated());

        return $this->respondMutation('Propuesta actualizada y reenviada a revisión.', new ProposalResource($updated));
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
        $pending = $this->proposalService->getPending($perPage);

        return $this->respondList(
            data: ProposalResource::collection($pending),
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
        $proposals = $this->adminService->listProposals($filters, $perPage);

        return $this->respondList(
            data: ProposalResource::collection($proposals),
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

        $updated = $this->adminService->reviewProposal(
            proposal: $proposal,
            admin: $admin,
            status: $validated['status'],
            adminNotes: $validated['admin_notes'] ?? null,
        );

        return $this->respondMutation('Propuesta revisada correctamente.', new ProposalResource($updated));
    }
}
