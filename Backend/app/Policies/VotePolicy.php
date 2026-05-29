<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;

/**
 * Política de permisos para los votos.
 */
class VotePolicy
{

    /**
     * Indica si se pueden listar los votos.
     *
     * @param User $user Usuario autenticado.
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Indica si se puede ver un voto.
     *
     * @param User $user Usuario autenticado.
     * @param Vote $vote Voto a consultar.
     * @return bool
     */
    public function view(User $user, Vote $vote): bool
    {
        return false;
    }

    /**
     * Indica si se puede crear un voto para un item.
     *
     * @param User $user Usuario autenticado.
     * @param Item $item Item sobre el que se vota.
     * @return bool
     */
    public function create(User $user, Item $item): bool
    {
        return $item->status === Item::STATUS_ACTIVE;
    }

    /**
     * Indica si se puede actualizar un voto.
     *
     * @param User $user Usuario autenticado.
     * @param Vote $vote Voto a modificar.
     * @return bool
     */
    public function update(User $user, Vote $vote): bool
    {
      return $user->id === $vote->user_id;
    }

    /**
     * Indica si se puede eliminar un voto.
     *
     * @param User $user Usuario autenticado.
     * @param Vote $vote Voto a eliminar.
     * @return bool
     */
    public function delete(User $user, Vote $vote): bool
    {
	    return $user->id === $vote->user_id;
    }

    /**
     * Indica si se puede restaurar un voto.
     *
     * @param User $user Usuario autenticado.
     * @param Vote $vote Voto a restaurar.
     * @return bool
     */
    public function restore(User $user, Vote $vote): bool
    {
        return false;
    }

    /**
     * Indica si se puede borrar definitivamente un voto.
     *
     * @param User $user Usuario autenticado.
     * @param Vote $vote Voto a borrar definitivamente.
     * @return bool
     */
    public function forceDelete(User $user, Vote $vote): bool
    {
        return false;
    }
}
