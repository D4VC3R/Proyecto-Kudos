<?php

namespace App\Http\Controllers\Proposal;

use App\Actions\ReviewProposalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminProposalsRequest;
use App\Http\Requests\Admin\ReviewProposalRequest;
use App\Http\Requests\Proposals\DeleteProposalRequest;
use App\Http\Requests\Proposals\ListPendingProposalsRequest;
use App\Http\Requests\Proposals\ShowProposalRequest;
use App\Http\Requests\Proposals\StoreProposalRequest;
use App\Http\Requests\Proposals\UpdateProposalRequest;
use App\Http\Resources\ProposalDetailResource;
use App\Http\Resources\ProposalListResource;
use App\Jobs\ProcessProposalImagesJob;
use App\Models\Proposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProposalController extends Controller
{
	public function __construct()
	{
	}

	public function store(StoreProposalRequest $request): JsonResponse
	{
		$validated = $request->validated();
		$rawItems = [];

		$imagePath = $request->input('image_path');
		if (is_string($imagePath) && $imagePath !== '') {
			$rawItems[] = $imagePath;
		}

		$imageFile = $request->file('image_path');
		if ($imageFile) {
			$path = Storage::disk('local')->putFile('temp_uploads', $imageFile);
			if (is_string($path) && $path !== '') {
				$rawItems[] = $path;
			}
		}

		unset($validated['image_path']);

		$proposal = Proposal::create(array_merge($validated, [
			'creator_id' => $request->user()->id,
			'status' => Proposal::STATUS_PENDING,
			'images' => [],
		]));

		if (!empty($rawItems)) {
			try {
				ProcessProposalImagesJob::dispatchSync($proposal, $rawItems);
			} catch (\Throwable $e) {
				// Si falla el procesamiento, la propuesta ya existe sin imágenes nuevas
				// Log está dentro del job, aquí solo capturamos para no fallar la creación
			}
		}

		return $this->respondMutation('Propuesta creada correctamente.', new ProposalDetailResource($proposal->fresh()), status: 201);
	}

	public function myProposals(Request $request): JsonResponse
	{
		$proposals = Proposal::with(['category:id,name,slug', 'reviewer:id,name'])
			->where('creator_id', $request->user()->id)
			->latest()
			->get();

		return $this->respondList(
			data: ProposalListResource::collection($proposals->fresh()),
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
			new ProposalDetailResource($proposal->load(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name']))
		);
	}

	public function update(UpdateProposalRequest $request, Proposal $proposal): JsonResponse
	{
		$proposal->update(array_merge($request->validated(), [
			'status' => Proposal::STATUS_PENDING,
		]));

		return $this->respondMutation('Propuesta actualizada y reenviada a revisión.', new ProposalDetailResource($proposal->fresh()));
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
			data: ProposalListResource::collection($pending),
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
			data: ProposalListResource::collection($proposals),
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

		return $this->respondMutation('Propuesta revisada correctamente.', new ProposalDetailResource($updated));
	}
}