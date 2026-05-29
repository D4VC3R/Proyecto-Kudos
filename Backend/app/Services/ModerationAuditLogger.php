<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para registrar auditorías de moderación.
 */
class ModerationAuditLogger
{
    /**
     * Registra la revisión de una propuesta por parte de un administrador.
     *
     * @param Proposal $proposal Propuesta revisada.
     * @param User $admin Administrador que realizó la revisión.
     * @param string $status Nuevo estado de la propuesta (e.g., 'approved', 'rejected').
     * @param string|null $notes Notas adicionales del administrador sobre la revisión.
     */
	public function logProposalReview(Proposal $proposal, User $admin, string $status, ?string $notes = null): void
	{
		Log::channel('moderation')->info('admin.proposal.review', [
			'proposal_id' => $proposal->id,
			'admin_id' => $admin->id,
			'status' => $status,
			'admin_notes' => $notes,
			'reviewed_at' => now()->toIso8601String(),
		]);
	}

    /**
     * Registra una acción de moderación realizada sobre un ítem.
     *
     * @param Item $item Ítem moderado.
     * @param User $admin Administrador que realizó la acción de moderación.
     * @param string $action Tipo de acción realizada (e.g., 'edited', 'hidden', 'deleted').
     * @param array $changes Cambios específicos realizados al ítem (opcional).
     * @param string|null $reason Razón para la acción de moderación (opcional).
     */
	public function logItemModeration(Item $item, User $admin, string $action, array $changes = [], ?string $reason = null): void
	{
		Log::channel('moderation')->info('admin.item.moderation', [
			'item_id' => $item->id,
			'admin_id' => $admin->id,
			'action' => $action,
			'changes' => $changes,
			'reason' => $reason,
			'moderated_at' => now()->toIso8601String(),
		]);
	}

    /**
     * Registra un cambio en el estado de ban de un usuario por parte de un administrador.
     *
     * @param User $targetUser Usuario objetivo del cambio de ban.
     * @param User $admin Administrador que realizó el cambio de ban.
     * @param string $action Tipo de acción realizada (e.g., 'banned', 'unbanned').
     * @param array $context Información adicional sobre el cambio de ban (opcional).
     */
	public function logUserBanChange(User $targetUser, User $admin, string $action, array $context = []): void
	{
		Log::channel('moderation')->info('admin.user.ban', [
			'user_id' => $targetUser->id,
			'admin_id' => $admin->id,
			'action' => $action,
			'context' => $context,
			'changed_at' => now()->toIso8601String(),
		]);
	}
}

