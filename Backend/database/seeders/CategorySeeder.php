<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Videojuegos',
                'slug' => 'videojuegos',
                'description' => 'Descubre y vota por los mejores videojuegos de todos los tiempos',
                'image' => 'https://via.placeholder.com/640x480.png?text=videojuegos',
            ],
            [
                'name' => 'Musica',
                'slug' => 'musica',
                'description' => 'Las mejores canciones, albumes y artistas musicales',
                'image' => 'https://via.placeholder.com/640x480.png?text=musica',
            ],
            [
                'name' => 'Albumes Musicales',
                'slug' => 'albumes-musicales',
                'description' => 'Albumes musicales relevantes a nivel mundial y en Espana',
                'image' => 'https://via.placeholder.com/640x480.png?text=albumes',
            ],
            [
                'name' => 'Artistas Musicales',
                'slug' => 'artistas-musicales',
                'description' => 'Artistas musicales destacados de Espana y del mundo',
                'image' => 'https://via.placeholder.com/640x480.png?text=artistas',
            ],
            [
                'name' => 'Libros',
                'slug' => 'libros',
                'description' => 'Libros publicados en Espana con titulo y descripcion en espanol',
                'image' => 'https://via.placeholder.com/640x480.png?text=libros',
            ],
            [
                'name' => 'Peliculas',
                'slug' => 'peliculas',
                'description' => 'El cine que ha marcado historia y entretenimiento',
                'image' => 'https://via.placeholder.com/640x480.png?text=peliculas',
            ],
            [
                'name' => 'Series',
                'slug' => 'series',
                'description' => 'Las series de television mas populares y aclamadas',
                'image' => 'https://via.placeholder.com/640x480.png?text=series',
            ],
            [
                'name' => 'Ciudades',
                'slug' => 'ciudades',
                'description' => 'Las ciudades mas bellas e interesantes del mundo',
                'image' => 'https://via.placeholder.com/640x480.png?text=ciudades',
            ],
            [
                'name' => 'Marcas',
                'slug' => 'marcas',
                'description' => 'Las marcas mas reconocidas e influyentes',
                'image' => 'https://via.placeholder.com/640x480.png?text=marcas',
            ],
            [
                'name' => 'Politicos',
                'slug' => 'politicos',
                'description' => 'Figuras politicas destacadas en la historia',
                'image' => 'https://via.placeholder.com/640x480.png?text=politicos',
            ],
            [
                'name' => 'Paises',
                'slug' => 'paises',
                'description' => 'Los paises mas fascinantes del planeta',
                'image' => 'https://via.placeholder.com/640x480.png?text=paises',
            ],
        ];

        foreach ($rows as $row) {
            Category::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'image' => $row['image'],
                ]
            );
        }
    }
}
