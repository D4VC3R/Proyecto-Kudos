<?php

namespace App\Services;

use App\Repositories\AdminRepository;
use App\Models\Item;
use App\Models\User;
use App\Models\Proposal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminService
{
    protected AdminRepository $adminRepository;
    protected ItemService $itemService;
    protected ProposalService $proposalService;
    protected ModerationAuditLogger $moderationAuditLogger;

    public function __construct(
        AdminRepository $adminRepository,
        ItemService $itemService,
        ProposalService $proposalService,
        ModerationAuditLogger $moderationAuditLogger,
    )
    {
        $this->adminRepository = $adminRepository;
        $this->itemService = $itemService;
        $this->proposalService = $proposalService;
        $this->moderationAuditLogger = $moderationAuditLogger;
    }

    /**
     * Devuelve usuarios paginados y resumen para administración.
     *
     * @param array $filters
     * @param int $perPage
     * @return array{users: LengthAwarePaginator, summary: array<string,int>}
     */
    public function listUsers(array $filters, int $perPage = 20): array
    {
        $users = $this->adminRepository->paginateUsers($filters, $perPage);
        $summary = $this->adminRepository->usersSummary();
        return [
            'users' => $users,
            'summary' => $summary,
        ];
    }

    /**
     * Devuelve items paginados para administración.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listItems(array $filters, int $perPage = 20)
    {
        return $this->adminRepository->paginateItems($filters, $perPage);
    }

    /**
     * Devuelve propuestas paginadas para administración.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listProposals(array $filters, int $perPage = 15)
    {
        return $this->adminRepository->paginateProposals($filters, $perPage);
    }

    /**
     * Banea un usuario desde administración.
     *
     * @param User $admin
     * @param User $targetUser
     * @param bool $isPermanent
     * @param int|null $days
     * @param string $reason
     * @return User
     */
    public function banUser(User $admin, User $targetUser, bool $isPermanent, ?int $days, string $reason): User
    {
        $targetUser->is_banned = true;
        $targetUser->banned_at = now();
        $targetUser->banned_until = $isPermanent ? null : now()->addDays((int) $days);
        $targetUser->ban_reason = $reason;
        $targetUser->banned_by = $admin->id;
        $targetUser->save();

        $targetUser->tokens()->delete();

        $this->moderationAuditLogger->logUserBanChange($targetUser, $admin, 'ban', [
            'is_permanent' => $isPermanent,
            'days' => $isPermanent ? null : $days,
            'reason' => $reason,
        ]);

        return $targetUser->fresh();
    }

    /**
     * Desbanea un usuario desde administración.
     *
     * @param User $admin
     * @param User $targetUser
     * @return User
     */
    public function unbanUser(User $admin, User $targetUser): User
    {
        $targetUser->is_banned = false;
        $targetUser->banned_at = null;
        $targetUser->banned_until = null;
        $targetUser->ban_reason = null;
        $targetUser->banned_by = null;
        $targetUser->save();

        $this->moderationAuditLogger->logUserBanChange($targetUser, $admin, 'unban');

        return $targetUser->fresh();
    }

    /**
     * Revoca los tokens de un usuario desde administración.
     *
     * @param User $admin
     * @param User $targetUser
     * @return int
     */
    public function revokeUserTokens(User $admin, User $targetUser): int
    {
        $revoked = $targetUser->tokens()->count();
        $targetUser->tokens()->delete();

        $this->moderationAuditLogger->logUserBanChange($targetUser, $admin, 'revoke_tokens', [
            'revoked_tokens' => $revoked,
        ]);

        return $revoked;
    }

    /**
     * Actualiza un item desde administración y registra la auditoría.
     *
     * @param User $admin
     * @param Item $item
     * @param array $payload
     * @param string|null $reason
     * @return Item
     */
    public function updateAdminItem(User $admin, Item $item, array $payload, ?string $reason = null): Item
    {
        $before = $item->only(['name', 'description', 'images', 'status', 'category_id']);
        $updated = $this->itemService->updateItem($item, $payload);
        $after = $updated->only(['name', 'description', 'images', 'status', 'category_id']);

        $this->moderationAuditLogger->logItemModeration(
            $updated,
            $admin,
            'admin_update_item',
            [
                'before' => $before,
                'after' => $after,
            ],
            $reason,
        );

        return $updated;
    }

    /**
     * Modera el estado de un item desde administración y registra la auditoría.
     *
     * @param User $admin
     * @param Item $item
     * @param string $newStatus
     * @param string|null $reason
     * @return Item
     */
    public function moderateItemStatus(User $admin, Item $item, string $newStatus, ?string $reason = null): Item
    {
        $previousStatus = $item->status;
        $updated = $this->itemService->updateItem($item, ['status' => $newStatus]);

        $this->moderationAuditLogger->logItemModeration(
            $updated,
            $admin,
            'admin_moderate_item_status',
            ['from' => $previousStatus, 'to' => $newStatus],
            $reason,
        );

        return $updated;
    }

    /**
     * Revisa una propuesta desde administración.
     *
     * @param Proposal $proposal
     * @param User $admin
     * @param string $status
     * @param string|null $adminNotes
     * @return Proposal
     */
    public function reviewProposal(Proposal $proposal, User $admin, string $status, ?string $adminNotes): Proposal
    {
        return $this->proposalService->review($proposal, $admin, $status, $adminNotes);
    }

    // Aquí se añadirán otros métodos de administración (items, proposals, etc.)
}

