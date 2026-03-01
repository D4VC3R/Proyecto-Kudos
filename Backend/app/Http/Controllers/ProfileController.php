<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
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

		return response()->json([
			'profile' => new ProfileResource($user->profile)
		], 200);
	}

	public function update(UpdateProfileRequest $request): JsonResponse
	{
		$user = $request->user();
		$validatedData = $request->validated();

		$profile = $this->profileService->updateProfile($user, $validatedData);

		return response()->json([
			'message' => 'Perfil actualizado correctamente.',
			'profile' => new ProfileResource($profile)
		], 200);
	}
}
