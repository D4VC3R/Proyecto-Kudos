<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

/**
 * Política de permisos para las propuestas.
 */
class ProposalPolicy
{
    /**
     * Indica si se pueden listar las propuestas.
     *
     * @param User $user Usuario autenticado.
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Indica si se puede ver una propuesta.
     *
     * @param User $user Usuario autenticado.
     * @param Proposal $proposal Propuesta a consultar.
     * @return bool
     */
    public function view(User $user, Proposal $proposal): bool
    {
        return $user->hasRole('admin') || $proposal->creator_id === $user->id;
    }

    /**
     * Indica si se puede crear una propuesta.
     *
     * @param User $user Usuario autenticado.
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Indica si se puede revisar cualquier propuesta.
     *
     * @param User $user Usuario autenticado.
     * @return bool
     */
    public function reviewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede actualizar una propuesta.
     *
     * @param User $user Usuario autenticado.
     * @param Proposal $proposal Propuesta a modificar.
     * @return bool
     */
    public function update(User $user, Proposal $proposal): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $proposal->creator_id === $user->id
            && in_array($proposal->status, [Proposal::STATUS_CHANGES_REQUESTED, Proposal::STATUS_PENDING], true);
    }

    /**
     * Indica si se puede eliminar una propuesta.
     *
     * @param User $user Usuario autenticado.
     * @param Proposal $proposal Propuesta a eliminar.
     * @return bool
     */
    public function delete(User $user, Proposal $proposal): bool
    {
        if (!in_array($proposal->status, [Proposal::STATUS_PENDING, Proposal::STATUS_CHANGES_REQUESTED], true)) {
            return false;
        }

        return $user->hasRole('admin') || $proposal->creator_id === $user->id;
    }

    /**
     * Indica si se puede revisar una propuesta.
     *
     * @param User $user Usuario autenticado.
     * @param Proposal $proposal Propuesta a revisar.
     * @return bool
     */
    public function review(User $user, Proposal $proposal): bool
    {
        return $user->hasRole('admin') && $proposal->creator_id !== $user->id;
    }
}
