<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicProfileResource;
use App\Models\User;
use App\Services\PublicProfileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Perfil público (F3-03). GET /api/users/{username}: público. Un perfil sin `is_public` da 404 a todos, también a
 * los admin, salvo a su dueño, que lo ve (con `is_owner = true`). No se distingue de un nombre que no existe.
 */
class PublicProfileController extends Controller
{
    public function __construct(protected PublicProfileService $publicProfileService) {}

    public function show(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user();
        $user = User::query()->where('username', mb_strtolower($username))->with('profile')->first();
        $isOwner = $user !== null && $viewer !== null && $viewer->id === $user->id;

        if ($user === null || (! $isOwner && ! $user->hasPublicProfile())) {
            throw (new ModelNotFoundException)->setModel(User::class, [$username]);
        }

        return $this->respondData(new PublicProfileResource(
            $user,
            $this->publicProfileService->topByCategory($user, $viewer),
            $this->publicProfileService->publicLists($user),
            $this->publicProfileService->recentReviews($user),
            $isOwner,
        ));
    }
}
