<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\MinimalProfileResource;
use App\Jobs\ProcessUserAvatarJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
		$validated = $request->validated();
		$avatarInput = null;

		if (is_string($validated['avatar'] ?? null) && $validated['avatar'] !== '') {
			$avatarInput = $validated['avatar'];
		}

		if ($request->hasFile('avatar')) {
			$path = Storage::disk('local')->putFile('temp_uploads', $request->file('avatar'));
			if (is_string($path) && $path !== '') {
				$avatarInput = $path;
			}
		}

		unset($validated['avatar']);

		$profile->update($validated);

		if (is_string($avatarInput) && $avatarInput !== '') {
			ProcessUserAvatarJob::dispatchSync($request->user(), $avatarInput);
		}

		return $this->respondMutation(
			'Perfil actualizado correctamente.',
			new ProfileResource($profile->fresh()),
		);
	}
}

