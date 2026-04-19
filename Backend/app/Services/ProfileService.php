<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use App\Repositories\ProfileRepository;

class ProfileService
{
	public function __construct(protected ProfileRepository $profileRepository)
	{}

    public function getUserStatistics(User $user): array
    {
        // Estadísticas de votos
        $totalVotes = $user->votes()->where('type', 'vote')->count();
        $totalSkips = $user->votes()->where('type', 'skip')->count();
        $averageScore = $user->votes()->where('type', 'vote')->avg('score');

        // Categoría favorita (la más votada positivamente)
        $favoriteCategoryId = $user->votes()
            ->where('type', 'vote')
            ->join('items', 'votes.item_id', '=', 'items.id')
            ->select('items.category_id')
            ->groupBy('items.category_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('items.category_id');

        $favoriteCategoryName = null;
        if ($favoriteCategoryId) {
            $favoriteCategory = \App\Models\Category::find($favoriteCategoryId);
            $favoriteCategoryName = $favoriteCategory ? $favoriteCategory->name : null;
        }

        // Propuestas y comentarios
        $acceptedProposals = $user->proposals()->accepted()->count();
        $totalComments = $user->comments()->count();

        return [
            'total_votes' => $totalVotes,
            'total_skips' => $totalSkips,
            'average_score' => $averageScore ? round((float) $averageScore, 1) : null,
            'favorite_category' => $favoriteCategoryName,
            'accepted_proposals' => $acceptedProposals,
            'total_comments' => $totalComments,
            'current_login_streak' => $user->login_streak_count,
            'max_login_streak' => $user->max_login_streak_count,
            'total_kudos' => $user->total_kudos,
        ];
    }

	public function updateProfile(User $user, array $data): Profile
	{
		// Como el perfil se creó junto al usuario, accedemos directamente a la relación.
		// Delegamos la acción de actualizar al repositorio.
		return $this->profileRepository->update($user->profile, $data);
	}
}