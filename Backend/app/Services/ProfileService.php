<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use App\Repositories\ProfileRepository;

class ProfileService
{
	public function __construct(protected ProfileRepository $profileRepository)
	{}

	public function updateProfile(User $user, array $data): Profile
	{
		// Como el perfil se creó junto al usuario, accedemos directamente a la relación.
		// Delegamos la acción de actualizar al repositorio.
		return $this->profileRepository->update($user->profile, $data);
	}
}