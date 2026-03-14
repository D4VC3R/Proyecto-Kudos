<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Users\BanUserAction;
use App\Actions\Admin\Users\RevokeUserTokensAction;
use App\Actions\Admin\Users\UnbanUserAction;
use App\Http\Requests\BanUserRequest;
use App\Models\User;
use App\Queries\Admin\Users\ListAdminUsersQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        protected ListAdminUsersQuery $listAdminUsersQuery,
        protected BanUserAction $banUserAction,
        protected UnbanUserAction $unbanUserAction,
        protected RevokeUserTokensAction $revokeUserTokensAction,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'is_banned' => $request->query('is_banned'),
            'ban_state' => $request->query('ban_state'),
            'role' => $request->query('role'),
        ];

        $result = $this->listAdminUsersQuery->execute(
            filters: $filters,
            perPage: (int) $request->query('per_page', 20),
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
        if (!$admin instanceof User) {
            return response()->json(['message' => 'No se pudo obtener el administrador autenticado.'], 500);
        }

        if ($admin->id === $user->id) {
            return response()->json(['message' => 'No puedes banear tu propia cuenta.'], 422);
        }

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

    public function unban(Request $request, User $user): JsonResponse
    {
        $admin = $request->user();
        if (!$admin instanceof User) {
            return response()->json(['message' => 'No se pudo obtener el administrador autenticado.'], 500);
        }

        $updatedUser = $this->unbanUserAction->execute($admin, $user);

        return response()->json([
            'message' => 'Usuario desbaneado correctamente.',
            'data' => $updatedUser,
        ]);
    }

    public function revokeTokens(Request $request, User $user): JsonResponse
    {
        $admin = $request->user();
        if (!$admin instanceof User) {
            return response()->json(['message' => 'No se pudo obtener el administrador autenticado.'], 500);
        }

        if ($admin->id === $user->id) {
            return response()->json(['message' => 'No puedes revocar tu propia sesión desde esta acción.'], 422);
        }

        $revoked = $this->revokeUserTokensAction->execute($admin, $user);

        return response()->json([
            'message' => 'Sesiones del usuario revocadas correctamente.',
            'meta' => [
                'revoked_tokens' => $revoked,
            ],
        ]);
    }
}

