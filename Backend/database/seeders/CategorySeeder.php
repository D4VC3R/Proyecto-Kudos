<?php

namespace Database\Seeders;

use App\Catalog\Providers\IgdbGameProvider;
use App\Catalog\Providers\TmdbMovieProvider;
use App\Catalog\Providers\TmdbTvProvider;
use App\Catalog\Providers\WikidataPlaceProvider;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeder para la tabla de categorías (Kudos 2.0, F1-10).
 * Siembra `peliculas`, `series`, `videojuegos` (F4-01) y `destinos` (F4-02) activas, con el attribute_schema de sus
 * proveedores (TMDB, IGDB y Wikidata). Ya no crea las categorías heredadas y no borra las
 * que ya existan (eso lo hace migrate:fresh en el reset).
 */
class CategorySeeder extends Seeder
{
    /**
     * Ejecuta el seeder para crear o actualizar las categorías en la base de datos.
     * Las categorías se crean sin portada: el frontend pinta el icono de cada una.
     */
    public function run(): void
    {
        // Las categorías heredadas (libros, álbumes, artistas, países, ciudades y políticos) ya no se siembran.
        // Sin portada: las que había eran fotos de terceros (carteles y capturas) sin licencia para
        // redistribuirlas, y el seeder se ejecuta en producción.
        $rows = [
            [
                'name' => 'Películas',
                'slug' => 'peliculas',
                'description' => 'Clásicos, estrenos y joyas por descubrir: puntúa las películas que has visto y guarda en tu lista las que te faltan.',
                'image' => null,
                'icon' => 'clapperboard',
                'sort_order' => 1,
                'is_active' => true,
                'attribute_schema' => TmdbMovieProvider::ATTRIBUTE_SCHEMA,
            ],
            [
                'name' => 'Series',
                'slug' => 'series',
                'description' => 'De las que se ven de un tirón a las que duran temporadas: valora las series que has visto y apunta las siguientes.',
                'image' => null,
                'icon' => 'tv',
                'sort_order' => 2,
                'is_active' => true,
                'attribute_schema' => TmdbTvProvider::ATTRIBUTE_SCHEMA,
            ],
            [
                'name' => 'Videojuegos',
                'slug' => 'videojuegos',
                'description' => 'Grandes sagas, indies y clásicos de todas las plataformas: puntúa los juegos que has terminado y guarda los pendientes.',
                'image' => null,
                'icon' => 'gamepad-2',
                'sort_order' => 3,
                'is_active' => true,
                'attribute_schema' => IgdbGameProvider::ATTRIBUTE_SCHEMA,
            ],
            [
                'name' => 'Destinos',
                'slug' => 'destinos',
                'description' => 'Ciudades y países de todo el mundo: valora los que ya conoces y descubre los que te quedan por visitar.',
                'image' => null,
                'icon' => 'map-pin',
                'sort_order' => 4,
                'is_active' => true,
                'attribute_schema' => WikidataPlaceProvider::ATTRIBUTE_SCHEMA,
            ],
        ];

        foreach ($rows as $row) {
            Category::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'image' => $row['image'],
                    'attribute_schema' => $row['attribute_schema'],
                    'is_active' => $row['is_active'],
                    'sort_order' => $row['sort_order'],
                    'icon' => $row['icon'],
                ]
            );
        }
    }
}
