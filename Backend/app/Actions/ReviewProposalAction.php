<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\Proposal;
use App\Models\User;
use App\Services\KudosService;
use App\Services\ModerationAuditLogger;
use Illuminate\Support\Facades\DB;

class ReviewProposalAction
{
	public function __construct(
		protected KudosService $kudosService,
		protected ModerationAuditLogger $moderationAuditLogger,
	) {}

	public function execute(Proposal $proposal, User $admin, string $status, ?string $adminNotes): Proposal
	{
		return DB::transaction(function () use ($proposal, $admin, $status, $adminNotes) {

			// Solo bloqueamos para actualización concurrente. El estado ya fue validado en el Request.
			$lockedProposal = Proposal::lockForUpdate()->find($proposal->id);

			// Actualizar Propuesta
			$lockedProposal->update([
				'status' => $status,
				'reviewed_by' => $admin->id,
				'reviewed_at' => now(),
				'admin_notes' => $adminNotes,
			]);

			// Auditoría
			$this->moderationAuditLogger->logProposalReview($lockedProposal, $admin, $status, $adminNotes);

			// Consecuencias
			if ($status === Proposal::STATUS_ACCEPTED) {
				$this->handleAcceptedProposal($lockedProposal);
			}

			return $lockedProposal->fresh(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name']);
		});
	}

	private function handleAcceptedProposal(Proposal $proposal): void
	{
		Item::create($proposal->only(['name', 'description', 'images', 'extra_data', 'creator_id', 'category_id']));

		$creator = User::lockForUpdate()->findOrFail($proposal->creator_id);

		$this->kudosService->processProposalAccepted($creator, $proposal->id);

		$creator->increment('creations_accepted');
	}
}