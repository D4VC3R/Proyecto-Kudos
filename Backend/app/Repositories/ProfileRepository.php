<?php

namespace App\Repositories;

use App\Models\Profile;

class ProfileRepository
{
	/**
	 * Actualiza un perfil existente.
	 */
	public function update(Profile $profile, array $data): Profile
	{

		$profile->update($data);

		return $profile;
	}
}