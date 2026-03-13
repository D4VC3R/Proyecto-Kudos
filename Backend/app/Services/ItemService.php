<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use App\Repositories\ItemRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemService
{
	protected ItemRepository $itemRepository;

	public function __construct(ItemRepository $itemRepository)
	{
		$this->itemRepository = $itemRepository;
	}

	// Obtener items activos con filtros y paginación
	public function getActiveItems(array $filters, int $perPage = 10): LengthAwarePaginator
	{
		return $this->itemRepository->getActiveItems($filters, $perPage);
	}

	/**
	 * Enriquece los items con información del contexto del usuario.
	 * Pre-calcula permisos y carga votos de forma eficiente.
	 */
	public function enrichItemsWithUserContext($items, ?User $user): void
	{
		if (!$user || $items->isEmpty()) {
			return;
		}

		// Cargar todos los votos del usuario en UNA query
		$this->itemRepository->loadUserVotes($items, $user);

		// Pre-calcular permisos
		$isAdmin = $user->hasRole('admin');

		foreach ($items as $item) {
			$hasVoted = $item->userVote !== null;

			// Attachar datos calculados como atributos del modelo
			$item->setAttribute('can_vote', $item->status === Item::STATUS_ACTIVE && !$hasVoted);
			$item->setAttribute('can_edit', $isAdmin);
			$item->setAttribute('can_delete', $isAdmin);
			$item->setAttribute('is_creator', $item->creator_id === $user->id);
			$item->setAttribute('is_admin_user', $isAdmin);
		}
	}

	public function getItemsByUser(User $user): Collection
	{
		return $this->itemRepository->getItemsByUser($user);
	}

	public function createItem(array $data, User $user): Item
	{
		$itemData = [
			'id' => Str::uuid(),
			'name' => $data['name'],
			'description' => $data['description'],
			'images' => $data['images'] ?? null,
			'status' => Item::STATUS_ACTIVE,
			'category_id' => $data['category_id'],
			'creator_id' => $user->id,
			'vote_avg' => 0,
			'vote_count' => 0,
		];

		$item = $this->itemRepository->create($itemData);

		// Asociar tags si existen
		if (!empty($data['tag_ids'])) {
			$item->tags()->attach($data['tag_ids']);
		}

		return $item->load(['category', 'creator', 'tags']);
	}

	public function updateItem(Item $item, array $data): Item
	{
		// Actualizar datos básicos
		$updateData = [
			'name' => $data['name'] ?? $item->name,
			'description' => $data['description'] ?? $item->description,
			'images' => array_key_exists('images', $data) ? $data['images'] : $item->images,
			'category_id' => $data['category_id'] ?? $item->category_id,
			'status' => $data['status'] ?? $item->status,
		];

		$item = $this->itemRepository->update($item, $updateData);

		// Actualizar tags si se proporcionan
		if (isset($data['tag_ids'])) {
			$item->tags()->sync($data['tag_ids']);
		}

		return $item->load(['category', 'creator', 'tags']);
	}

	public function deleteItem(Item $item): bool
	{
		return $this->itemRepository->delete($item);
	}
}
