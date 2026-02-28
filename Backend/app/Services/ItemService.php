<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use App\Repositories\ItemRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
class ItemService
{
	protected ItemRepository $itemRepository;

	public function __construct(ItemRepository $itemRepository)
	{
		$this->itemRepository = $itemRepository;
	}

	// Obtener items aceptados con filtros y paginación
	public function getAcceptedItems(array $filters, int $perPage = 10): LengthAwarePaginator
	{
		return $this->itemRepository->getAcceptedItems($filters, $perPage);
	}

	// Obtener items pendientes para revisión
	public function getPendingItems(int $perPage = 10): LengthAwarePaginator
	{
		return $this->itemRepository->getPendingItems($perPage);
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
			'image' => $data['image'] ?? null,
			'state' => Item::STATE_PENDING, // Siempre empieza como pending
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
		// Validar que el item esté en estado pending
		if ($item->state !== Item::STATE_PENDING) {
			throw new \Exception('Solo se pueden editar items en estado pendiente.');
		}

		// Actualizar datos básicos
		$updateData = [
			'name' => $data['name'] ?? $item->name,
			'description' => $data['description'] ?? $item->description,
			'image' => $data['image'] ?? $item->image,
			'category_id' => $data['category_id'] ?? $item->category_id,
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
		if ($item->state !== Item::STATE_PENDING) {
			throw new \Exception('Solo se pueden eliminar items en estado pendiente.');
		}

		return $this->itemRepository->delete($item);
	}

	public function acceptItem(Item $item, User $admin): Item
	{
		if ($item->state !== Item::STATE_PENDING) {
			throw new \Exception('El item ya ha sido revisado.');
		}

		$item = $this->itemRepository->updateState($item, Item::STATE_ACCEPTED, $admin);

		$item->adminReviews()->create([
			'id' => Str::uuid(),
			'admin_id' => $admin->id,
			'final_state' => Item::STATE_ACCEPTED,
			'reason' => null,
		]);

		return $item;
	}

	public function rejectItem(Item $item, User $admin, ?string $reason = null): Item
	{
		if ($item->state !== Item::STATE_PENDING) {
			throw new \Exception('El item ya ha sido revisado.');
		}

		$item = $this->itemRepository->updateState($item, Item::STATE_REJECTED, $admin);

		// Crear registro de revisión administrativa con el motivo
		$item->adminReviews()->create([
			'id' => Str::uuid(),
			'admin_id' => $admin->id,
			'final_state' => Item::STATE_REJECTED,
			'reason' => $reason,
		]);

		return $item;
	}

	public function forceDeleteItem(Item $item): bool
	{
		// Eliminar votos asociados
		$item->votes()->delete();

		// Eliminar relaciones con tags
		$item->tags()->detach();

		// Eliminar el item
		return $this->itemRepository->delete($item);
	}

	public function recalculateVoteStats(Item $item): Item
	{
		$votes = $item->votes;

		$voteCount = $votes->count();
		$voteAvg = $voteCount > 0 ? round($votes->avg('score'), 2) : 0;

		return $this->itemRepository->update($item, [
			'vote_count' => $voteCount,
			'vote_avg' => $voteAvg,
		]);
	}



}