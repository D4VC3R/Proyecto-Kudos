<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->where('email', 'admin@kudos.com')->first()
            ?? User::query()->orderBy('created_at')->first();

        if (!$creator instanceof User) {
            throw new \RuntimeException('No hay usuarios disponibles para creator_id.');
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

    private function seedCategoryFromSnapshot(string $categorySlug, string $snapshotPath, User $creator, string $label): void
    {
        if (!is_file($snapshotPath)) {
            throw new \RuntimeException(
                'No existe snapshot local de ' . $label . ' para seeding: ' . $snapshotPath
            );
        }

        $category = Category::query()->where('slug', $categorySlug)->first();
        if (!$category instanceof Category) {
            throw new \RuntimeException('No existe la categoria ' . $categorySlug . ' en base de datos.');
        }

        $payload = json_decode((string) File::get($snapshotPath), true);
        if (!is_array($payload)) {
            throw new \RuntimeException('El snapshot no contiene JSON valido: ' . $snapshotPath);
        }

        $rows = $payload['items'] ?? [];
        if (!is_array($rows)) {
            throw new \RuntimeException('El snapshot no contiene un arreglo en items: ' . $snapshotPath);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $skipped++;
                continue;
            }

            $name = mb_substr(trim((string) ($row['name'] ?? '')), 0, 240);
            if ($name === '') {
                $skipped++;
                continue;
            }

            $description = trim((string) ($row['description'] ?? ''));
            if ($description === '') {
                $description = 'Item importado automaticamente desde snapshot local.';
            }

            $images = Arr::wrap($row['images'] ?? []);
            $extraData = is_array($row['extra_data'] ?? null) ? $row['extra_data'] : [];

            $existing = $this->findExistingItem($category->id, $name, $extraData);

            if ($existing instanceof Item) {
                $existing->fill([
                    'description' => $description,
                    'images' => $images,
                    'extra_data' => $extraData,
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
                'extra_data' => $extraData,
                'status' => Item::STATUS_ACTIVE,
                'vote_avg' => 0,
                'vote_count' => 0,
                'creator_id' => $creator->id,
                'category_id' => $category->id,
            ]);
            $created++;
        }

        $this->command?->info(sprintf(
            'Items %s cargados desde snapshot local. created=%d updated=%d skipped=%d',
            $label,
            $created,
            $updated,
            $skipped
        ));
    }

    /**
     * @param array<string,mixed> $extraData
     */
    private function findExistingItem(string $categoryId, string $name, array $extraData): ?Item
    {
        $sourceId = trim((string) ($extraData['source_id'] ?? ''));
        if ($sourceId !== '') {
            $bySource = Item::query()
                ->where('category_id', $categoryId)
                ->where('extra_data->source_id', $sourceId)
                ->first();

            if ($bySource instanceof Item) {
                return $bySource;
            }
        }

        $query = Item::query()
            ->where('category_id', $categoryId)
            ->where('name', $name);

        $artist = trim((string) ($extraData['artist'] ?? ''));
        if ($artist !== '') {
            $query->where('extra_data->artist', $artist);
        }

        $author = trim((string) ($extraData['author'] ?? ''));
        if ($author !== '') {
            $query->where('extra_data->author', $author);
        }

        return $query->first();
    }
}
