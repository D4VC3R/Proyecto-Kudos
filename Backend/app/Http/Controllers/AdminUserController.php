<?php

namespace App\Http\Controllers;

use App\Http\Resources\Admin\AdminUserDetailResource;
use App\Http\Resources\Admin\AdminUserListResource;
use App\Http\Requests\BanUserRequest;
use App\Http\Requests\ListAdminUsersRequest;
use App\Http\Requests\RevokeUserTokensRequest;
use App\Http\Requests\ShowAdminUserRequest;
use App\Http\Requests\UnbanUserRequest;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(
        protected AdminService $adminService,
    ) {
    }

    public function index(ListAdminUsersRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'is_banned' => $validated['is_banned'] ?? null,
            'ban_state' => $validated['ban_state'] ?? null,
            'role' => $validated['role'] ?? null,
            'sort_by' => $validated['sort_by'] ?? null,
            'sort_direction' => $validated['sort_direction'] ?? null,
        ];

        $result = $this->adminService->listUsers(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 20),
        );

        $users = $result['users'];

        return $this->respondList(
            data: AdminUserListResource::collection($users),
            meta: [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'summary' => $result['summary'],
            ],
        );
    }

    public function show(ShowAdminUserRequest $request, User $user): JsonResponse
    {
        $detail = $this->adminService->getUserDetail($user);

        if (!$detail instanceof User) {
            return $this->respondError(
                code: 'not_found',
                message: 'Usuario no encontrado.',
                status: 404,
            );
        }

        return $this->respondData(new AdminUserDetailResource($detail));
    }

    public function ban(BanUserRequest $request, User $user): JsonResponse
    {
        $admin = $request->user();

        $updatedUser = $this->adminService->banUser(
            admin: $admin,
            targetUser: $user,
            isPermanent: (bool) $request->boolean('is_permanent'),
            days: $request->integer('days'),
            reason: (string) $request->input('reason'),
        );

        return $this->respondMutation('Usuario baneado correctamente.', new AdminUserListResource($updatedUser));
    }

    public function unban(UnbanUserRequest $request, User $user): JsonResponse
    {
        $admin = $request->user();

        $updatedUser = $this->adminService->unbanUser($admin, $user);

        return $this->respondMutation('Usuario desbaneado correctamente.', new AdminUserListResource($updatedUser));
    }

    public function revokeTokens(RevokeUserTokensRequest $request, User $user): JsonResponse
    {
        $admin = $request->user();

        $revoked = $this->adminService->revokeUserTokens($admin, $user);

        return $this->respondMutation(
            'Sesiones del usuario revocadas correctamente.',
            meta: [
                'revoked_tokens' => $revoked,
            ],
        );
    }
}
