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
                'description' => 'De Ciudadano Kane hasta Barbie pasando por Scarface y Pretty Woman, las mejores películas de la historia compiten en esta categoría para llegar a la primera posición del ránking.',
                'image' => null,
                'icon' => 'clapperboard',
                'sort_order' => 1,
                'is_active' => true,
                'attribute_schema' => TmdbMovieProvider::ATTRIBUTE_SCHEMA,
            ],
            [
                'name' => 'Series',
                'slug' => 'series',
                'description' => 'Ya seas de Chicas Gilmore o Breaking Bad, en esta categoría encontrarás series de todos los tiempos luchando entre ellas por tener la nota media más alta de la categoría.',
                'image' => null,
                'icon' => 'tv',
                'sort_order' => 2,
                'is_active' => true,
                'attribute_schema' => TmdbTvProvider::ATTRIBUTE_SCHEMA,
            ],
            [
                'name' => 'Videojuegos',
                'slug' => 'videojuegos',
                'description' => 'La batalla definitiva por alzarse con el titulo de mejor videojuego de la historia ha comenzado, ¿Qué juego se alzará con la victoria?',
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
