<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdateItemRequest;
use App\Http\Requests\Admin\ListAdminItemsRequest;
use App\Http\Requests\Admin\ModerateItemRequest;
use App\Http\Resources\ItemDetailResource;
use App\Http\Resources\ItemListResource;
use App\Models\Item;
use App\Services\ModerationAuditLogger;
use Illuminate\Http\JsonResponse;

// <-- Inyectamos el logger directamente

/*
 * Controlador para la gestión de items desde el panel de administración.
 * Aquí se manejan las operaciones CRUD y de moderación específicas para los items.
 * Se ha simplificado el código eliminando el repositorio y utilizando Eloquent directamente,
 * aprovechando los scopes para mantener la lógica de filtrado y ordenación limpia.
 */
class AdminItemController extends Controller
{
	public function __construct(
		protected ModerationAuditLogger $moderationAuditLogger,
	) {}

	public function index(ListAdminItemsRequest $request): JsonResponse
	{
		$validated = $request->validated();
		$perPage = (int) ($validated['per_page'] ?? 20);

		// Uso directo de Eloquent, aprovechando los scopes.
		$items = Item::query()
			->with(['category:id,name,slug', 'creator:id,name,email'])
			->adminApplyFilters($validated)
			->adminApplySorting($validated['sort_by'] ?? 'created_at', $validated['sort_direction'] ?? 'desc')
			->paginate(min(max($perPage, 1), 100)); // Lógica de protección que estaba en el repo

		return $this->respondList(
			data: ItemListResource::collection($items),
			meta: [
				'current_page' => $items->currentPage(),
				'last_page' => $items->lastPage(),
				'per_page' => $items->perPage(),
				'total' => $items->total(),
			],
		);
	}

	public function update(AdminUpdateItemRequest $request, Item $item): JsonResponse
	{
		$admin = $request->user();
		$payload = $request->validated();
		$reason = $payload['moderation_reason'] ?? null;
		unset($payload['moderation_reason']);

		// Guardar estado previo para la auditoría
		$before = $item->only(['name', 'description', 'images', 'status', 'category_id']);

		// Actualizar directamente usando Eloquent
		$item->update($payload);

		// Guardar estado posterior
		$after = $item->only(['name', 'description', 'images', 'status', 'category_id']);
		$this->moderationAuditLogger->logItemModeration(
			$item, $admin, 'admin_update_item', ['before' => $before, 'after' => $after], $reason
		);

		return $this->respondMutation('Item actualizado por administración.', new ItemDetailResource($item->fresh()));
	}

	public function moderate(ModerateItemRequest $request, Item $item): JsonResponse
	{
		$admin = $request->user();
		$status = $request->validated()['status'];
		$reason = $request->validated()['reason'] ?? null;

		$previousStatus = $item->status;

		$item->update(['status' => $status]);

		$this->moderationAuditLogger->logItemModeration(
			$item, $admin, 'admin_moderate_item_status', ['from' => $previousStatus, 'to' => $status], $reason
		);

		return $this->respondMutation('Estado del item actualizado por administración.', new ItemDetailResource($item->fresh()));
	}
}