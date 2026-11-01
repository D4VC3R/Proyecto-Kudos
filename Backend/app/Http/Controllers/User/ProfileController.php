<?php

namespace App\Http\Controllers\User;

use App\Actions\DeleteAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\DeleteAccountRequest;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Http\Resources\AccountExportResource;
use App\Http\Resources\MinimalProfileResource;
use App\Http\Resources\ProfileResource;
use App\Jobs\ProcessUserAvatarJob;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

		if (array_key_exists('username', $validated)) {
			try {
				$request->user()->forceFill(['username' => $validated['username']])->save();
			} catch (UniqueConstraintViolationException) {
				throw ValidationException::withMessages(['username' => 'Ese nombre de usuario ya está en uso.']);
			}
			unset($validated['username']);
		}

		$profile->update($validated);

		if (is_string($avatarInput) && $avatarInput !== '') {
			ProcessUserAvatarJob::dispatchSync($request->user(), $avatarInput);
		}

		return $this->respondMutation(
			'Perfil actualizado correctamente.',
			new ProfileResource($profile->fresh()),
		);
	}

	/**
	 * Exporta en JSON los datos del usuario (F3-08, RGPD: acceso y portabilidad).
	 * Incluye lo borrado con borrado suave que aún se guarda y los ítems borrados que aparecen en sus datos.
	 */
	public function export(Request $request): JsonResponse
	{
		$withTrashed = fn ($query) => $query->withTrashed();

		$user = $request->user()->load([
			'profile',
			'votes' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
			'votes.item' => $withTrashed,
			'votes.item.category:id,slug',
			'comments' => fn ($query) => $query->withTrashed()->orderBy('created_at')->orderBy('id'),
			'comments.item' => $withTrashed,
			'lists' => fn ($query) => $query->withTrashed()->orderBy('created_at')->orderBy('id'),
			'lists.listItems.item' => $withTrashed,
			'likedLists' => fn ($query) => $query->withTrashed()->orderBy('list_likes.created_at'),
			'proposals' => fn ($query) => $query->withTrashed()->orderBy('created_at')->orderBy('id'),
			'proposals.category:id,slug',
			'kudosTransactions' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
		]);

		return $this->respondData(new AccountExportResource($user));
	}

	/**
	 * Borra la cuenta del usuario y sus datos (F3-08, RGPD: supresión). Exige la contraseña.
	 */
	public function destroy(DeleteAccountRequest $request, DeleteAccountAction $action): JsonResponse
	{
		$user = $request->user();

		// Las cuentas de administrador no se borran desde el perfil: sus listas editoriales desaparecerían de la portada.
		if ($user->hasRole('admin')) {
			return $this->respondError('forbidden', 'Una cuenta de administrador no se puede borrar desde el perfil.', status: 403);
		}

		$action->execute($user);

		return $this->respondMutation('Tu cuenta y tus datos se han borrado.');
	}
}
