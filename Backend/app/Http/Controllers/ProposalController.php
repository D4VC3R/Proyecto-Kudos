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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Actions\ReviewProposalAction;

class ProposalController extends Controller
{
    public function __construct(
    ) {
    }

    public function store(StoreProposalRequest $request): JsonResponse
    {
        $proposal = Proposal::create(array_merge($request->validated(), [
            'creator_id' => $request->user()->id,
            'status' => Proposal::STATUS_PENDING,
        ]));

        return $this->respondMutation('Propuesta creada correctamente.', new ProposalResource($proposal), status: 201);
    }

    public function myProposals(Request $request): JsonResponse
    {
        $proposals = Proposal::with(['category:id,name,slug', 'reviewer:id,name'])
            ->where('creator_id', $request->user()->id)
            ->latest()
            ->get();

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
        $proposal->update(array_merge($request->validated(), [
            'status' => Proposal::STATUS_PENDING,
        ]));

        return $this->respondMutation('Propuesta actualizada y reenviada a revisión.', new ProposalResource($proposal->fresh()));
    }

    public function destroy(DeleteProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $proposal->delete();

        return $this->respondMutation('Propuesta eliminada correctamente.');
    }

    public function pending(ListPendingProposalsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);
        
        $pending = Proposal::with(['creator:id,name', 'category:id,name,slug'])
            ->where('status', Proposal::STATUS_PENDING)
            ->latest()
            ->paginate($perPage);

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
        $proposals = Proposal::with(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name'])
            ->adminApplyFilters($filters)
            ->latest()
            ->paginate(min(max($perPage, 1), 100));

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

    public function review(ReviewProposalRequest $request, Proposal $proposal, ReviewProposalAction $action): JsonResponse
    {
        $admin = $request->user();
        $validated = $request->validated();

        $updated = $action->execute(
            $proposal,
            $admin,
            $validated['status'],
            $validated['admin_notes'] ?? null
        );

        return $this->respondMutation('Propuesta revisada correctamente.', new ProposalResource($updated));
    }
}
