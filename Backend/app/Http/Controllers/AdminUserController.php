<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Users\BanUserAction;
use App\Actions\Admin\Users\RevokeUserTokensAction;
use App\Actions\Admin\Users\UnbanUserAction;
use App\Http\Requests\BanUserRequest;
use App\Http\Requests\ListAdminUsersRequest;
use App\Http\Requests\RevokeUserTokensRequest;
use App\Http\Requests\UnbanUserRequest;
use App\Models\User;
use App\Queries\Admin\Users\ListAdminUsersQuery;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(
        protected ListAdminUsersQuery $listAdminUsersQuery,
        protected BanUserAction $banUserAction,
        protected UnbanUserAction $unbanUserAction,
        protected RevokeUserTokensAction $revokeUserTokensAction,
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
        ];

        $result = $this->listAdminUsersQuery->execute(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 20),
        );

        $users = $result['users'];

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'summary' => $result['summary'],
            ],
        ]);
    }

    public function ban(BanUserRequest $request, User $user): JsonResponse
    {
        $admin = $request->user();

        $updatedUser = $this->banUserAction->execute(
            admin: $admin,
            targetUser: $user,
            isPermanent: (bool) $request->boolean('is_permanent'),
            days: $request->integer('days'),
            reason: (string) $request->input('reason'),
        );

        return response()->json([
            'message' => 'Usuario baneado correctamente.',
            'data' => $updatedUser,
        ]);
    }

    public function unban(UnbanUserRequest $request, User $user): JsonResponse
    {
        $admin = $request->user();

        $updatedUser = $this->unbanUserAction->execute($admin, $user);

        return response()->json([
            'message' => 'Usuario desbaneado correctamente.',
            'data' => $updatedUser,
        ]);
    }

    public function revokeTokens(RevokeUserTokensRequest $request, User $user): JsonResponse
    {
        $admin = $request->user();

        $revoked = $this->revokeUserTokensAction->execute($admin, $user);

        return response()->json([
            'message' => 'Sesiones del usuario revocadas correctamente.',
            'meta' => [
                'revoked_tokens' => $revoked,
            ],
        ]);
    }
}

