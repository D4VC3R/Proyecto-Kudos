<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\Trait\DownloadsSeedImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Seeder para cargar items de ejemplo en la base de datos a partir de snapshots locales.
 */
class ItemSeeder extends Seeder
{
	use DownloadsSeedImages;

    /**
     * Ejecuta el seeding de items para varias categorías utilizando snapshots locales.
     * Busca un usuario admin o el primer usuario disponible para asignar como creador de los items.
     * Para cada categoría, carga los datos desde el snapshot correspondiente y crea o actualiza los items en la base de datos.
     * Al finalizar, muestra un resumen de los items creados, actualizados y omitidos para cada categoría.
     *
     * @return void
     * @throws RuntimeException Si no hay usuarios disponibles para asignar como creador o si los snapshots no son válidos.
     */
	public function run(): void
	{
		$creator = User::query()->where('email', 'admin@kudos.com')->first()
			?? User::query()->orderBy('created_at')->first();

		if (!$creator instanceof User) {
			throw new RuntimeException('No hay usuarios disponibles para creator_id.');
		}

		$this->seedCategoryFromSnapshot('videojuegos', base_path('database/seed-data/videojuegos/rawg_videojuegos_es.json'), $creator, 'videojuegos');
		$this->seedCategoryFromSnapshot('peliculas', base_path('database/seed-data/peliculas/tmdb_peliculas_es.json'), $creator, 'peliculas');
		$this->seedCategoryFromSnapshot('series', base_path('database/seed-data/series/tmdb_series_es.json'), $creator, 'series');
		$this->seedCategoryFromSnapshot('paises', base_path('database/seed-data/paises/restcountries_paises_es.json'), $creator, 'paises');
		$this->seedCategoryFromSnapshot('ciudades', base_path('database/seed-data/ciudades/spain_cities_es.json'), $creator, 'ciudades');
		$this->seedCategoryFromSnapshot('politicos', base_path('database/seed-data/politicos/wikidata_politicos_es.json'), $creator, 'politicos');
		$this->seedCategoryFromSnapshot('albumes-musicales', base_path('database/seed-data/albumes-musicales/itunes_albumes_es_global.json'), $creator, 'albumes musicales');
		$this->seedCategoryFromSnapshot('artistas-musicales', base_path('database/seed-data/artistas-musicales/wikidata_artistas_musicales_es_global.json'), $creator, 'artistas musicales');
		$this->seedCategoryFromSnapshot('libros', base_path('database/seed-data/libros/google_books_libros_es.json'), $creator, 'libros');
	}

    /**
     * Carga items en la base de datos a partir de un snapshot local para una categoría específica.
     * Verifica que el snapshot exista y contenga un JSON válido, luego procesa cada fila para crear o actualizar items en la base de datos.
     * Si un item con el mismo nombre ya existe en la categoría, se actualiza su descripción, imágenes y estado. Si no existe, se crea uno nuevo.
     * Al finalizar, muestra un resumen de los items creados, actualizados y omitidos.
     *
     * @param string $categorySlug El slug de la categoría a la que pertenecen los items.
     * @param string $snapshotPath La ruta al archivo JSON del snapshot local.
     * @param User $creator El usuario que se asignará como creador de los items.
     * @param string $label Una etiqueta descriptiva para mostrar en los mensajes de resumen.
     * @return void
     * @throws RuntimeException Si el snapshot no existe, no es válido o si la categoría no se encuentra en la base de datos.
     */
	private function seedCategoryFromSnapshot(string $categorySlug, string $snapshotPath, User $creator, string $label): void
	{
		if (!is_file($snapshotPath)) {
			throw new RuntimeException(
				'No existe snapshot local de ' . $label . ' para seeding: ' . $snapshotPath
			);
		}

		$category = Category::query()->where('slug', $categorySlug)->first();
		if (!$category instanceof Category) {
			throw new RuntimeException('No existe la categoria ' . $categorySlug . ' en la base de datos.');
		}

		$payload = json_decode((string)File::get($snapshotPath), true);
		if (!is_array($payload)) {
			throw new RuntimeException('El snapshot no contiene un JSON valido: ' . $snapshotPath);
		}

		$rows = $payload['items'] ?? [];
		if (!is_array($rows)) {
			throw new RuntimeException('El snapshot no contiene un array en items: ' . $snapshotPath);
		}

		$created = 0;
		$updated = 0;
		$skipped = 0;

		foreach ($rows as $row) {
			if (!is_array($row)) {
				$skipped++;
				continue;
			}

			$name = mb_substr(trim((string)($row['name'] ?? '')), 0, 240);
			if ($name === '') {
				$skipped++;
				continue;
			}

			$description = trim((string)($row['description'] ?? ''));
			if ($description === '') {
				$description = 'Item importado.';
			}

			$images = Arr::wrap($row['images'] ?? []);
			$images = $this->normalizeSeedImages($images, $categorySlug, 'items');


			$existing = $this->findExistingItem($category->id, $name);

			if ($existing instanceof Item) {
				$existing->fill([
					'description' => $description,
					'images' => $images,
					'status' => Item::STATUS_ACTIVE,
				]);
				$existing->save();
				$updated++;
				continue;
			}

			Item::query()->create([
				'name' => $name,
				'description' => $description,
				'images' => $images,
				'status' => Item::STATUS_ACTIVE,
				'vote_avg' => 0,
				'vote_count' => 0,
				'creator_id' => $creator->id,
				'category_id' => $category->id,
			]);
			$created++;
		}

		$this->command?->info(sprintf(
			'Items %s cargados en la base de datos. created=%d updated=%d skipped=%d',
			$label,
			$created,
			$updated,
			$skipped
		));
	}


    /**
     * Busca un item existente en la base de datos por su nombre dentro de una categoría específica.
     * Retorna el item encontrado o null si no existe.
     *
     * @param string $categoryId El ID de la categoría a la que pertenece el item.
     * @param string $name El nombre del item a buscar.
     * @return Item|null El item encontrado o null si no existe.
     */
	private function findExistingItem(string $categoryId, string $name): ?Item
	{
		return Item::query()
			->where('category_id', $categoryId)
			->where('name', $name)
			->first();
	}
}
