<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryFieldDefinition;
use Illuminate\Database\Seeder;

class CategoryFieldDefinitionSeeder extends Seeder
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function definitionsBySlug(): array
    {
        return [
            'videojuegos' => [
                ['key' => 'developer', 'label' => 'Desarrollador', 'type' => 'string', 'required' => true, 'sort_order' => 10],
                ['key' => 'publisher', 'label' => 'Publisher', 'type' => 'string', 'required' => true, 'sort_order' => 20],
                ['key' => 'platforms', 'label' => 'Plataformas', 'type' => 'string_list', 'required' => true, 'sort_order' => 30, 'rules' => ['min_items' => 1, 'max_items' => 8]],
                ['key' => 'genre', 'label' => 'Genero de videojuego', 'type' => 'enum', 'required' => false, 'sort_order' => 40, 'options' => ['accion', 'aventura', 'rpg', 'estrategia', 'deportes', 'simulacion', 'indie']],
                ['key' => 'where_to_buy', 'label' => 'Donde comprarlo', 'type' => 'url', 'required' => false, 'sort_order' => 50],
                ['key' => 'release_date', 'label' => 'Fecha de salida', 'type' => 'date', 'required' => false, 'sort_order' => 60],
                ['key' => 'metacritic_score', 'label' => 'Puntuacion Metacritic', 'type' => 'integer', 'required' => false, 'sort_order' => 70, 'rules' => ['min' => 0, 'max' => 100]],
            ],
            'peliculas' => [
                ['key' => 'director', 'label' => 'Director', 'type' => 'string', 'required' => true, 'sort_order' => 10],
                ['key' => 'release_year', 'label' => 'Ano de lanzamiento', 'type' => 'integer', 'required' => true, 'sort_order' => 20, 'rules' => ['min' => 1888, 'max' => 2100]],
                ['key' => 'actors', 'label' => 'Actores', 'type' => 'string_list', 'required' => false, 'sort_order' => 30, 'rules' => ['max_items' => 20]],
                ['key' => 'platforms', 'label' => 'Plataformas disponibles', 'type' => 'string_list', 'required' => false, 'sort_order' => 40, 'rules' => ['max_items' => 15]],
            ],
            'series' => [
                ['key' => 'seasons', 'label' => 'Temporadas', 'type' => 'integer', 'required' => true, 'sort_order' => 10, 'rules' => ['min' => 1, 'max' => 100]],
                ['key' => 'platforms', 'label' => 'Plataformas disponibles', 'type' => 'string_list', 'required' => false, 'sort_order' => 20, 'rules' => ['max_items' => 15]],
                ['key' => 'actors', 'label' => 'Actores', 'type' => 'string_list', 'required' => false, 'sort_order' => 30, 'rules' => ['max_items' => 30]],
                ['key' => 'release_year', 'label' => 'Ano de lanzamiento', 'type' => 'integer', 'required' => true, 'sort_order' => 40, 'rules' => ['min' => 1900, 'max' => 2100]],
                ['key' => 'director', 'label' => 'Director', 'type' => 'string', 'required' => false, 'sort_order' => 50],
            ],
            'ciudades' => [
                ['key' => 'country', 'label' => 'Pais', 'type' => 'string', 'required' => true, 'sort_order' => 10],
                ['key' => 'population', 'label' => 'Poblacion', 'type' => 'integer', 'required' => false, 'sort_order' => 20, 'rules' => ['min' => 0]],
                ['key' => 'language', 'label' => 'Idioma', 'type' => 'string', 'required' => false, 'sort_order' => 30],
                ['key' => 'places_of_interest', 'label' => 'Lugares de interes', 'type' => 'string_list', 'required' => false, 'sort_order' => 40, 'rules' => ['max_items' => 30]],
            ],
            'paises' => [
                ['key' => 'continent', 'label' => 'Continente', 'type' => 'enum', 'required' => true, 'sort_order' => 10, 'options' => ['europa', 'asia', 'america', 'africa', 'oceania']],
                ['key' => 'population', 'label' => 'Poblacion', 'type' => 'integer', 'required' => false, 'sort_order' => 20, 'rules' => ['min' => 0]],
                ['key' => 'language', 'label' => 'Idioma', 'type' => 'string', 'required' => false, 'sort_order' => 30],
                ['key' => 'interesting_cities', 'label' => 'Ciudades interesantes', 'type' => 'string_list', 'required' => false, 'sort_order' => 40, 'rules' => ['max_items' => 50]],
                ['key' => 'main_religion', 'label' => 'Religion predominante', 'type' => 'string', 'required' => false, 'sort_order' => 50],
            ],
            'politicos' => [
                ['key' => 'party', 'label' => 'Partido', 'type' => 'string', 'required' => true, 'sort_order' => 10],
                ['key' => 'age', 'label' => 'Edad', 'type' => 'integer', 'required' => false, 'sort_order' => 20, 'rules' => ['min' => 18, 'max' => 120]],
                ['key' => 'quotes', 'label' => 'Citas celebres', 'type' => 'string_list', 'required' => false, 'sort_order' => 30, 'rules' => ['max_items' => 20]],
                ['key' => 'position', 'label' => 'Cargo', 'type' => 'enum', 'required' => true, 'sort_order' => 40, 'options' => ['presidente', 'ministro', 'militante', 'diputado', 'senador', 'alcalde']],
            ],
            // Categorias actuales no detalladas por negocio: set base flexible.
            'musica' => [
                ['key' => 'artist', 'label' => 'Artista', 'type' => 'string', 'required' => true, 'sort_order' => 10],
                ['key' => 'genre', 'label' => 'Genero musical', 'type' => 'string', 'required' => false, 'sort_order' => 20],
                ['key' => 'release_year', 'label' => 'Ano de lanzamiento', 'type' => 'integer', 'required' => false, 'sort_order' => 30, 'rules' => ['min' => 1800, 'max' => 2100]],
            ],
            'albumes-musicales' => [
                ['key' => 'artist', 'label' => 'Artista', 'type' => 'string', 'required' => true, 'sort_order' => 10],
                ['key' => 'genre', 'label' => 'Genero musical', 'type' => 'string', 'required' => false, 'sort_order' => 20],
                ['key' => 'release_year', 'label' => 'Ano de lanzamiento', 'type' => 'integer', 'required' => false, 'sort_order' => 30, 'rules' => ['min' => 1800, 'max' => 2100]],
                ['key' => 'origin_country', 'label' => 'Pais de origen', 'type' => 'string', 'required' => false, 'sort_order' => 40],
                ['key' => 'listening_url', 'label' => 'URL de escucha', 'type' => 'url', 'required' => false, 'sort_order' => 50],
            ],
            'artistas-musicales' => [
                ['key' => 'origin_country', 'label' => 'Pais de origen', 'type' => 'string', 'required' => false, 'sort_order' => 10],
                ['key' => 'genre', 'label' => 'Genero principal', 'type' => 'string', 'required' => false, 'sort_order' => 20],
                ['key' => 'active_since_year', 'label' => 'Activo desde', 'type' => 'integer', 'required' => false, 'sort_order' => 30, 'rules' => ['min' => 1800, 'max' => 2100]],
                ['key' => 'website', 'label' => 'Sitio web', 'type' => 'url', 'required' => false, 'sort_order' => 40],
            ],
            'libros' => [
                ['key' => 'author', 'label' => 'Autor', 'type' => 'string', 'required' => true, 'sort_order' => 10],
                ['key' => 'publisher', 'label' => 'Editorial', 'type' => 'string', 'required' => false, 'sort_order' => 20],
                ['key' => 'published_year', 'label' => 'Ano de publicacion', 'type' => 'integer', 'required' => false, 'sort_order' => 30, 'rules' => ['min' => 1500, 'max' => 2100]],
                ['key' => 'isbn_13', 'label' => 'ISBN-13', 'type' => 'string', 'required' => false, 'sort_order' => 40],
                ['key' => 'info_url', 'label' => 'URL informativa', 'type' => 'url', 'required' => false, 'sort_order' => 50],
                ['key' => 'origin_country', 'label' => 'Pais de publicacion', 'type' => 'string', 'required' => false, 'sort_order' => 60],
            ],
            'marcas' => [
                ['key' => 'industry', 'label' => 'Industria', 'type' => 'string', 'required' => true, 'sort_order' => 10],
                ['key' => 'origin_country', 'label' => 'Pais de origen', 'type' => 'string', 'required' => false, 'sort_order' => 20],
                ['key' => 'website', 'label' => 'Sitio web', 'type' => 'url', 'required' => false, 'sort_order' => 30],
            ],
        ];
    }

    public function run(): void
    {
        $definitions = $this->definitionsBySlug();

        foreach ($definitions as $slug => $rows) {
            $category = Category::query()->where('slug', $slug)->first();
            if (!$category) {
                $this->command?->warn("No se encontro la categoria '{$slug}' para cargar su esquema de extra_data.");
                continue;
            }

            foreach ($rows as $row) {
                CategoryFieldDefinition::query()->updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'key' => $row['key'],
                    ],
                    [
                        'label' => $row['label'],
                        'type' => $row['type'],
                        'required' => (bool) ($row['required'] ?? false),
                        'options' => $row['options'] ?? null,
                        'rules' => $row['rules'] ?? null,
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                        'is_filterable' => (bool) ($row['is_filterable'] ?? false),
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

