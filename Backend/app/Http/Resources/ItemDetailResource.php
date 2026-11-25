<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar el detalle de un ítem.
 * Incluye información completa del ítem, su categoría, creador, estado de votación del usuario, y permisos de edición/eliminación.
 * Se utiliza para mostrar la vista detallada de un ítem, proporcionando toda la información relevante y acciones disponibles según el rol del usuario.
 * Desde la F1-06 incluye los campos del catálogo (original_title, released_at, attributes, slug) y la atribución de las fuentes.
 * Desde la F2-01, user_vote incluye type.
 * Desde la F4-04, cada imagen incluye license, license_url, author y source_url (pie de foto de Commons).
 */
class ItemDetailResource extends JsonResource
{
	/**
	 * Nombre, web y licencia de cada fuente externa para la atribución (F1-06, legal/cumplimiento §1).
	 * La licencia solo se indica cuando obliga a atribuir o se quiere enlazar; igdb y steam desde la F4-01 (la atribución visible es la F4-04).
	 */
	protected const SOURCES = [
		'tmdb' => ['name' => 'TMDB', 'url' => 'https://www.themoviedb.org', 'license' => null, 'license_url' => null],
		'wikipedia_es' => ['name' => 'Wikipedia', 'url' => 'https://es.wikipedia.org', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/deed.es'],
		'wikidata' => ['name' => 'Wikidata', 'url' => 'https://www.wikidata.org', 'license' => 'CC0 1.0', 'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/deed.es'],
		'igdb' => ['name' => 'IGDB', 'url' => 'https://www.igdb.com', 'license' => null, 'license_url' => null],
		'steam' => ['name' => 'Steam', 'url' => 'https://store.steampowered.com', 'license' => null, 'license_url' => null],
	];

	public function toArray(Request $request): array
	{
		/** @var Item $item */
		$item = $this->resource;
		$user = $request->user();

		return [
			'id' => $item->id,
			'name' => $item->name,
			'description' => $item->description,
			'original_title' => $item->original_title,
			'released_at' => $item->released_at?->toDateString(),
			'attributes' => (object) $this->catalogAttributes($item),
			'slug' => $item->slug,
			'images' => collect($item->images ?? [])
				->map(fn($img) => [
					'variants' => [
						'thumb' => $img['variants']['thumb'] ?? null,
						'banner' => $img['variants']['banner'] ?? null,
					],
					'meta' => $img['meta'] ?? null,
					'alt' => $img['alt'] ?? null,
					'order' => $img['order'] ?? 0,
					// Crédito de la foto (F4-04): en Commons, licencia y autor del archivo; en TMDB e IGDB, "© titular de derechos" sin autor.
					'license' => $img['license'] ?? null,
					'license_url' => $this->imageLicenseUrl($img['license'] ?? null),
					'author' => $img['author'] ?? null,
					'source_url' => $img['source_url'] ?? null,
				])->toArray(),
			'status' => $item->status,
			'vote_avg' => (float)$item->vote_avg,
			'vote_count' => (int)$item->vote_count,
			'attribution' => [
				'data_source' => $this->dataSource($item),
				'description_source' => $this->descriptionSource($item),
			],

			'user_vote' => $this->when(
				$user && $item->relationLoaded('userVote') && $item->userVote,
				fn() => [
					'id' => $item->userVote->id,
					'type' => $item->userVote->type,
					'score' => $item->userVote->score,
					'voted_at' => $item->userVote->created_at->toIso8601String(),
				]
			),

			'can_vote' => $this->resolveCanVote($user, $item),
			'can_edit' => $this->when($user instanceof User, fn() => $user->can('update', $item)),
			'can_delete' => $this->when($user instanceof User, fn() => $user->can('delete', $item)),

			'category' => new CategoryResource($this->whenLoaded('category')),
			'creator' => $this->whenLoaded('creator', fn() => [
				'id' => $item->creator->id,
				'name' => $item->creator->name,
			]),
		];
	}

	protected function resolveCanVote(?User $user, Item $item): bool
	{
		if (!$user || $item->status !== Item::STATUS_ACTIVE) {
			return false;
		}

		if ($item->relationLoaded('userVote')) {
			return $item->userVote === null;
		}
		$hasVoted = $item->votes()->where('user_id', $user->id)->exists();

		return !$hasVoted;
	}

	/**
	 * Atributos del catálogo; se lee con getAttribute() porque $item->attributes es la propiedad interna de Eloquent.
	 */
	protected function catalogAttributes(Item $item): array
	{
		$attributes = $item->getAttribute('attributes');

		return is_array($attributes) ? $attributes : [];
	}

	/**
	 * Fuente principal de los datos (item_sources con is_primary), o null si el ítem no viene de ninguna (manual o heredado).
	 */
	protected function dataSource(Item $item): ?array
	{
		$source = $item->relationLoaded('sources')
			? $item->sources->firstWhere('is_primary', true)
			: $item->sources()->where('is_primary', true)->first();

		if ($source === null) {
			return null;
		}

		$known = self::SOURCES[$source->provider] ?? [];

		return [
			'name' => $known['name'] ?? $source->provider,
			'url' => $source->url ?? ($known['url'] ?? null),
		];
	}

	/**
	 * Procedencia de la descripción, o null si no hay descripción o es propia (manual).
	 */
	protected function descriptionSource(Item $item): ?array
	{
		$key = $item->description_source;

		if (blank($item->description) || blank($key) || $key === 'manual') {
			return null;
		}

		$known = self::SOURCES[$key] ?? [];

		return [
			'name' => $known['name'] ?? $key,
			'url' => $item->description_url ?? ($known['url'] ?? null),
			'license' => $known['license'] ?? null,
			'license_url' => $known['license_url'] ?? null,
		];
	}

	/**
	 * Enlace al texto de la licencia de una foto a partir de su nombre corto de Commons (LicenseShortName), o null si no es
	 * una licencia Creative Commons reconocible (dominio público, GFDL, "© titular de derechos"…). F4-04.
	 */
	protected function imageLicenseUrl(mixed $license): ?string
	{
		if (!is_string($license)) {
			return null;
		}

		$license = trim($license);

		if (preg_match('/^CC0(?: 1\.0)?$/i', $license) === 1) {
			return 'https://creativecommons.org/publicdomain/zero/1.0/deed.es';
		}

		if (preg_match('/^CC (BY(?:-NC)?(?:-SA|-ND)?) (\d\.\d)(?: ([a-z]{2,3}))?$/i', $license, $matches) !== 1) {
			return null;
		}

		// Licencias adaptadas a un país (p. ej. "CC BY-SA 3.0 de") o la IGO: https://creativecommons.org/licenses/by-sa/3.0/de/
		$jurisdiction = isset($matches[3]) && $matches[3] !== '' ? strtolower($matches[3]).'/' : '';

		return 'https://creativecommons.org/licenses/'.strtolower($matches[1]).'/'.$matches[2].'/'.$jurisdiction.'deed.es';
	}
}
