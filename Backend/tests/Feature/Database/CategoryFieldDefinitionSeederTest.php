<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\CategoryFieldDefinition;
use Database\Seeders\CategoryFieldDefinitionSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryFieldDefinitionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_loads_definitions_for_all_base_categories(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategoryFieldDefinitionSeeder::class);

        $expectedSlugs = [
            'videojuegos',
            'peliculas',
            'series',
            'ciudades',
            'paises',
            'politicos',
            'musica',
            'albumes-musicales',
            'artistas-musicales',
            'libros',
            'marcas',
        ];

        foreach ($expectedSlugs as $slug) {
            $category = Category::query()->where('slug', $slug)->first();
            $this->assertNotNull($category, "No existe la categoria esperada '{$slug}'.");

            $count = CategoryFieldDefinition::query()
                ->where('category_id', $category->id)
                ->where('is_active', true)
                ->count();

            $this->assertGreaterThan(0, $count, "La categoria '{$slug}' no tiene definiciones activas.");
        }
    }

    public function test_required_keys_exist_for_core_categories(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategoryFieldDefinitionSeeder::class);

        $requiredBySlug = [
            'videojuegos' => ['developer', 'publisher', 'platforms'],
            'peliculas' => ['director', 'release_year'],
            'series' => ['seasons', 'release_year'],
            'ciudades' => ['country'],
            'paises' => ['continent'],
            'politicos' => ['party', 'position'],
        ];

        foreach ($requiredBySlug as $slug => $keys) {
            $category = Category::query()->where('slug', $slug)->firstOrFail();

            foreach ($keys as $key) {
                $exists = CategoryFieldDefinition::query()
                    ->where('category_id', $category->id)
                    ->where('key', $key)
                    ->where('required', true)
                    ->exists();

                $this->assertTrue($exists, "Falta key requerida '{$key}' en categoria '{$slug}'.");
            }
        }
    }
}

