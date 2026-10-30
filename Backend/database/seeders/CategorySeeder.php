<?php

namespace Database\Seeders;

use App\Catalog\Providers\TmdbMovieProvider;
use App\Catalog\Providers\TmdbTvProvider;
use App\Models\Category;
use Database\Seeders\Trait\DownloadsSeedImages;
use Illuminate\Database\Seeder;

/**
 * Seeder para la tabla de categorías (Kudos 2.0, F1-10).
 * Siembra `peliculas` y `series` activas, con el attribute_schema de sus proveedores (TMDB), y crea
 * `videojuegos` y `destinos` inactivas hasta la F4. Ya no crea las categorías heredadas y no borra las
 * que ya existan (eso lo hace migrate:fresh en el reset).
 * Utiliza el trait DownloadsSeedImages para descargar y almacenar las portadas localmente.
 */
class CategorySeeder extends Seeder
{
    use DownloadsSeedImages;

    /**
     * Ejecuta el seeder para crear o actualizar las categorías en la base de datos.
     * Para cada categoría definida, se descarga la imagen asociada y se almacena localmente, actualizando la ruta de la imagen en la base de datos.
     */
    public function run(): void
    {
        // Las categorías heredadas (libros, álbumes, artistas, países, ciudades y políticos) ya no se siembran;
        // sus snapshots de database/seed-data/ se conservan (F1-10).
        $rows = [
            [
                'name' => 'Películas',
                'slug' => 'peliculas',
                'description' => 'De Ciudadano Kane hasta Barbie pasando por Scarface y Pretty Woman, las mejores películas de la historia compiten en esta categoría para llegar a la primera posición del ránking.',
                'image' => 'https://m.media-amazon.com/images/M/MV5BNjQyZDMzNDgtYmRmZS00Zjg2LWFjZjYtZDFjMDRlZTBjY2FiXkEyXkFqcGc@._V1_QL75_UY281_CR31,0,500,281_.jpg',
                'icon' => 'clapperboard',
                'sort_order' => 1,
                'is_active' => true,
                'attribute_schema' => TmdbMovieProvider::ATTRIBUTE_SCHEMA,
            ],
            [
                'name' => 'Series',
                'slug' => 'series',
                'description' => 'Ya seas de Chicas Gilmore o Breaking Bad, en esta categoría encontrarás series de todos los tiempos luchando entre ellas por tener la nota media más alta de la categoría.',
                'image' => 'https://images.bauerhosting.com/empire/2025/12/Best-TV-Shows-US-List.jpg',
                'icon' => 'tv',
                'sort_order' => 2,
                'is_active' => true,
                'attribute_schema' => TmdbTvProvider::ATTRIBUTE_SCHEMA,
            ],
            [
                'name' => 'Videojuegos',
                'slug' => 'videojuegos',
                'description' => 'La batalla definitiva por alzarse con el titulo de mejor videojuego de la historia ha comenzado, ¿Qué juego se alzará con la victoria?',
                'image' => 'https://sm.ign.com/t/ign_pk/screenshot/default/top-10-heroes-without-title-image_snt9.1280.png',
                'icon' => 'gamepad-2',
                'sort_order' => 3,
                'is_active' => false,
                'attribute_schema' => [],
            ],
            [
                'name' => 'Destinos',
                'slug' => 'destinos',
                'description' => 'Ciudades y países de todo el mundo: valora los que ya conoces y descubre los que te quedan por visitar.',
                'image' => null,
                'icon' => 'map-pin',
                'sort_order' => 4,
                'is_active' => false,
                'attribute_schema' => [],
            ],
        ];

        foreach ($rows as $row) {
            // Sin URL (destinos, hasta la F4) no hay portada que descargar.
            $imagePath = null;
            if ($row['image'] !== null) {
                $storedData = $this->downloadAndStoreImage($row['image'], $row['slug'], 'categories');
                $imagePath = is_array($storedData) ? ($storedData['path'] ?? $row['image']) : ($storedData ?? $row['image']);
            }

            Category::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'image' => $imagePath,
                    'attribute_schema' => $row['attribute_schema'],
                    'is_active' => $row['is_active'],
                    'sort_order' => $row['sort_order'],
                    'icon' => $row['icon'],
                ]
            );
        }
    }
}
