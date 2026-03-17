<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
	protected ProfileService $profileService;

	public function __construct(ProfileService $profileService){
		$this->profileService = $profileService;
	}
    /**
     * Display a listing of the resource.
     */
	public function show(Request $request): JsonResponse
	{
		$user = $request->user();

		return $this->respondData(new ProfileResource($user->profile));
	}

	public function update(UpdateProfileRequest $request): JsonResponse
	{
		$user = $request->user();
		$validatedData = $request->validated();

		$profile = $this->profileService->updateProfile($user, $validatedData);

		return $this->respondMutation(
			'Perfil actualizado correctamente.',
			new ProfileResource($profile),
		);
	}
}
