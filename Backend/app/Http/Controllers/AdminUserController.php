<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\ListAdminUsersRequest;
use App\Http\Requests\Admin\RevokeUserTokensRequest;
use App\Http\Requests\Admin\ShowAdminUserRequest;
use App\Http\Requests\Admin\UnbanUserRequest;
use App\Http\Resources\Admin\AdminUserDetailResource;
use App\Http\Resources\Admin\AdminUserListResource;
use App\Models\User;
use App\Services\ModerationAuditLogger;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
	public function __construct(
		protected ModerationAuditLogger $moderationAuditLogger,
	) {}

	public function index(ListAdminUsersRequest $request): JsonResponse
	{
		$validated = $request->validated();
		$perPage = (int) ($validated['per_page'] ?? 20);

		// Uso directo del modelo con los scopes limpios
		$users = User::query()
			->with('roles:uuid,name')
			->adminApplyFilters($validated)
			->adminApplySorting($validated)
			->paginate(min(max($perPage, 1), 100));

		return $this->respondList(
			data: AdminUserListResource::collection($users),
			meta: [
				'current_page' => $users->currentPage(),
				'last_page' => $users->lastPage(),
				'per_page' => $users->perPage(),
				'total' => $users->total(),
				'summary' => User::getAdminSummary(),
			],
		);
	}

	public function show(ShowAdminUserRequest $request, User $user): JsonResponse
	{
		// Eloquent Route Model Binding ya nos garantiza que el usuario existe
		$user->loadAdminDetails();

		return $this->respondData(new AdminUserDetailResource($user));
	}

	public function ban(BanUserRequest $request, User $user): JsonResponse
	{
		$admin = $request->user();
		$isPermanent = (bool) $request->boolean('is_permanent');
		$days = $request->integer('days');
		$reason = (string) $request->input('reason');

		// Lógica movida desde AdminService directamente al controlador
		$user->update([
			'is_banned' => true,
			'banned_at' => now(),
			'banned_until' => $isPermanent ? null : now()->addDays($days),
			'ban_reason' => $reason,
			'banned_by' => $admin->id,
		]);

		$user->tokens()->delete();

		$this->moderationAuditLogger->logUserBanChange($user, $admin, 'ban', [
			'is_permanent' => $isPermanent,
			'days' => $isPermanent ? null : $days,
			'reason' => $reason,
		]);

		return $this->respondMutation('Usuario baneado correctamente.', new AdminUserListResource($user->fresh()));
	}

	public function unban(UnbanUserRequest $request, User $user): JsonResponse
	{
		$admin = $request->user();

		$user->update([
			'is_banned' => false,
			'banned_at' => null,
			'banned_until' => null,
			'ban_reason' => null,
			'banned_by' => null,
		]);

		$this->moderationAuditLogger->logUserBanChange($user, $admin, 'unban');

		return $this->respondMutation('Usuario desbaneado correctamente.', new AdminUserListResource($user->fresh()));
	}

	public function revokeTokens(RevokeUserTokensRequest $request, User $user): JsonResponse
	{
		$admin = $request->user();

		$revoked = $user->tokens()->count();
		$user->tokens()->delete();

		$this->moderationAuditLogger->logUserBanChange($user, $admin, 'revoke_tokens', [
			'revoked_tokens' => $revoked,
		]);

		return $this->respondMutation(
			'Sesiones del usuario eliminadas correctamente.',
			meta: ['revoked_tokens' => $revoked]
		);
	}
}