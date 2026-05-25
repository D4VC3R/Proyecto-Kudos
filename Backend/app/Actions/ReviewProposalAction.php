<?php

namespace App\Actions;

use App\Contracts\Media\MediaStorageInterface;
use App\Models\Item;
use App\Models\Proposal;
use App\Models\User;
use App\Services\KudosService;
use App\Services\ModerationAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Acción para revisar una propuesta de creación.
 */
class ReviewProposalAction
{
    public function __construct(
        protected KudosService $kudosService,
        protected ModerationAuditLogger $moderationAuditLogger,
        protected MediaStorageInterface $mediaStorage,
    ) {}

	/**
	 * Revisa una propuesta, actualizando su estado y tomando las acciones correspondientes.
	 *
	 * @param Proposal $proposal La propuesta a revisar.
	 * @param User $admin El administrador que realiza la revisión.
	 * @param string $status El nuevo estado de la propuesta (aceptada, rechazada, etc.).
	 * @param string|null $adminNotes Notas adicionales del administrador.
	 * @return Proposal La propuesta actualizada con sus relaciones cargadas.
	 * @throws \Throwable
	 */
    public function execute(Proposal $proposal, User $admin, string $status, ?string $adminNotes): Proposal
    {
        return DB::transaction(function () use ($proposal, $admin, $status, $adminNotes) {

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

		/**
		 * Maneja las acciones necesarias cuando una propuesta es aceptada, (mover imágenes y crear el ítem).
		 *
		 * @param Proposal $proposal La propuesta que ha sido aceptada.
		 * @return void
		 */
    private function handleAcceptedProposal(Proposal $proposal): void
    {
        $categorySlug = $proposal->category()->value('slug') ?? 'general';
        $images = [];

        foreach ($proposal->images ?? [] as $image) {
            $variants = is_array($image) ? ($image['variants'] ?? []) : [];
            $meta = is_array($image) ? ($image['meta'] ?? []) : [];
            $disk = is_array($image) ? ($image['disk'] ?? 'public') : 'public';
            $alt = is_array($image) ? ($image['alt'] ?? null) : null;
            $order = is_array($image) ? ($image['order'] ?? 0) : 0;

            if (empty($variants) || !is_array($variants) || $disk !== 'public') {
                continue;
            }

            $movedVariants = [];
            foreach ($variants as $type => $path) {
                if (!is_string($path) || $path === '') continue;

                $filename = basename($path);
                $targetPath = 'items/' . $categorySlug . '/' . $proposal->id . '/' . $filename;

                $movedVariants[$type] = $this->mediaStorage->move('public', $path, 'public', $targetPath, 'public');
            }

            if (!empty($movedVariants)) {
                $images[] = [
                    'variants' => $movedVariants,
                    'meta' => $meta,
                    'disk' => 'public',
                    'alt' => $alt,
                    'order' => $order,
                ];
            }
        }

        Item::create(array_merge(
            $proposal->only(['name', 'description', 'creator_id', 'category_id']),
            ['images' => $images]
        ));

        $creator = User::lockForUpdate()->findOrFail($proposal->creator_id);
        $this->kudosService->processProposalAccepted($creator, $proposal->id);
        $creator->increment('creations_accepted');
    }
}