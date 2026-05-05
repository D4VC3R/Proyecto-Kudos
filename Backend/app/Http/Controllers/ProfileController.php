<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\MinimalProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function show(Request $request): JsonResponse
	{
		$user = $request->user();

		return $this->respondData(new ProfileResource($user->profile));
	}

	public function minimal(Request $request): JsonResponse
	{
		$user = $request->user();
		$user->load('profile:id,user_id,avatar');

		return $this->respondData(new MinimalProfileResource($user));
	}

	public function statistics(Request $request): JsonResponse
	{
		$stats = $request->user()->getProfileStatistics();

		return $this->respondData($stats);
	}

	public function update(UpdateProfileRequest $request): JsonResponse
	{
		$profile = $request->user()->profile;

		$profile->update($request->validated());

		return $this->respondMutation(
			'Perfil actualizado correctamente.',
			new ProfileResource($profile->fresh()),
		);
	}
}