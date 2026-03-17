<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Proposal;
use App\Models\User;
use App\Repositories\ProposalRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProposalService
{
    public function __construct(
        protected ProposalRepository $proposalRepository,
        protected KudosService $kudosService,
        protected ModerationAuditLogger $moderationAuditLogger,
    ) {
    }

    public function getPending(int $perPage = 15): LengthAwarePaginator
    {
        return $this->proposalRepository->getPending($perPage);
    }

    public function getByUser(User $user): Collection
    {
        return $this->proposalRepository->getByUser($user);
    }

    public function getForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->proposalRepository->getForAdmin($filters, $perPage);
    }

    public function createProposal(array $data, User $user): Proposal
    {
        return $this->proposalRepository->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'],
            'images' => $data['images'] ?? null,
            'status' => Proposal::STATUS_PENDING,
            'creator_id' => $user->id,
            'category_id' => $data['category_id'],
        ])->load(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name']);
    }

    public function updateAndResubmit(Proposal $proposal, array $data): Proposal
    {
        if ($proposal->trashed()) {
            abort(422, 'No se puede editar una propuesta eliminada.');
        }

        if ($proposal->status !== Proposal::STATUS_CHANGES_REQUESTED) {
            abort(422, 'Solo se pueden reenviar propuestas en estado changes_requested.');
        }

        return $this->proposalRepository->update($proposal, [
            'name' => $data['name'] ?? $proposal->name,
            'description' => $data['description'] ?? $proposal->description,
            'images' => array_key_exists('images', $data) ? $data['images'] : $proposal->images,
            'category_id' => $data['category_id'] ?? $proposal->category_id,
            'status' => Proposal::STATUS_PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_notes' => null,
        ]);
    }

    public function review(Proposal $proposal, User $admin, string $status, ?string $adminNotes): Proposal
    {
        return DB::transaction(function () use ($proposal, $admin, $status, $adminNotes) {
            $lockedProposal = Proposal::withTrashed()
                ->lockForUpdate()
                ->findOrFail($proposal->id);

            if (!$lockedProposal instanceof Proposal) {
                abort(500, 'No se pudo cargar la propuesta para revisión.');
            }

            if ($lockedProposal->trashed()) {
                abort(422, 'No se puede revisar una propuesta eliminada.');
            }

            if (!in_array($status, [
                Proposal::STATUS_ACCEPTED,
                Proposal::STATUS_REJECTED,
                Proposal::STATUS_CHANGES_REQUESTED,
            ], true)) {
                abort(422, 'Estado de revision no valido.');
            }

            if ($lockedProposal->status !== Proposal::STATUS_PENDING) {
                abort(422, 'Solo se pueden revisar propuestas en estado pending.');
            }

            if (
                in_array($status, [Proposal::STATUS_REJECTED, Proposal::STATUS_CHANGES_REQUESTED], true)
                && blank($adminNotes)
            ) {
                abort(422, 'admin_notes es obligatorio para rejected o changes_requested.');
            }

            $lockedProposal->update([
                'status' => $status,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_notes' => $adminNotes,
            ]);

            $this->moderationAuditLogger->logProposalReview($lockedProposal, $admin, $status, $adminNotes);

            if ($status === Proposal::STATUS_ACCEPTED) {
                Item::create([
                    'id' => (string) Str::uuid(),
                    'name' => $lockedProposal->name,
                    'description' => $lockedProposal->description,
                    'images' => $lockedProposal->images,
                    'status' => Item::STATUS_ACTIVE,
                    'vote_avg' => 0,
                    'vote_count' => 0,
                    'creator_id' => $lockedProposal->creator_id,
                    'category_id' => $lockedProposal->category_id,
                ]);

                $creator = User::lockForUpdate()->findOrFail($lockedProposal->creator_id);

                $this->kudosService->awardIfFirst(
                    user: $creator,
                    kudosAmount: KudosRules::rewardForAcceptedProposal(),
                    reason: KudosRules::reasonForAcceptedProposal(),
                    actionKey: KudosRules::actionKeyForAcceptedProposal($lockedProposal->id),
                    referenceType: Proposal::class,
                    referenceId: $lockedProposal->id,
                );

                $creator->increment('creations_accepted');
            }

            return $lockedProposal->fresh(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name']);
        });
    }

    public function deleteProposal(Proposal $proposal): bool
    {
        return $this->proposalRepository->delete($proposal);
    }
}
