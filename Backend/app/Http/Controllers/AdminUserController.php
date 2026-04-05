<?php

namespace App\Http\Controllers;

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
            data: $users->items(),
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

        return $this->respondData([
            'id' => $detail->id,
            'name' => $detail->name,
            'email' => $detail->email,
            'roles' => $detail->getRoleNames()->values()->all(),
            'is_banned' => (bool) $detail->is_banned,
            'banned_at' => $detail->banned_at?->toIso8601String(),
            'banned_until' => $detail->banned_until?->toIso8601String(),
            'ban_reason' => $detail->ban_reason,
            'total_kudos' => (int) $detail->total_kudos,
            'creations_accepted' => (int) $detail->creations_accepted,
            'proposals_count' => (int) $detail->proposals_count,
            'votes_count' => (int) $detail->votes_count,
            'comments_count' => (int) $detail->comments_count,
            'items_count' => (int) $detail->items_count,
            'reviewed_proposals_count' => (int) $detail->reviewed_proposals_count,
            'sessions_count' => (int) $detail->tokens()->count(),
            'profile' => [
                'city' => $detail->profile?->city,
                'birthdate' => $detail->profile?->birthdate?->format('Y-m-d'),
            ],
        ]);
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

        return $this->respondMutation('Usuario baneado correctamente.', $updatedUser);
    }

    public function unban(UnbanUserRequest $request, User $user): JsonResponse
    {
        $admin = $request->user();

        $updatedUser = $this->adminService->unbanUser($admin, $user);

        return $this->respondMutation('Usuario desbaneado correctamente.', $updatedUser);
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
