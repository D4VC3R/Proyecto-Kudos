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
                'description' => 'La batalla definitiva por alzarse con el titulo de mejor videojuego de la historia ha comenzado, ¿Qué juego se alzará con la victoria?',
                'image' => 'https://via.placeholder.com/640x480.png?text=videojuegos',
            ],
            [
                'name' => 'Albumes Musicales',
                'slug' => 'albumes-musicales',
                'description' => 'A lo largo de la historia se han compuesto muchos y muy buenos álbumes, entra y aporta tu granito de arena para conocer el mejor álbum de la historia.',
                'image' => 'https://via.placeholder.com/640x480.png?text=albumes',
            ],
            [
                'name' => 'Artistas Musicales',
                'slug' => 'artistas-musicales',
                'description' => 'Ayuda a tu artista favorito a alcanzar la primera posición del ránking. O lleva a ese artista que no soportas hasta la última posición, ¡entra y participa!',
                'image' => 'https://via.placeholder.com/640x480.png?text=artistas',
            ],
            [
                'name' => 'Libros',
                'slug' => 'libros',
                'description' => 'Shakespeare, Cervantes, Dostoyevski... todos ellos compiten por llevar sus obras a lo más alto del ránking, ¿Cuál es tu favorito?.',
                'image' => 'https://via.placeholder.com/640x480.png?text=libros',
            ],
            [
                'name' => 'Peliculas',
                'slug' => 'peliculas',
                'description' => 'De Ciudadano Kane hasta Barbie pasando por Scarface y Pretty Woman, las mejores películas de la historia compiten en esta categoría para llegar a la primera posición del ránking.',
                'image' => 'https://via.placeholder.com/640x480.png?text=peliculas',
            ],
            [
                'name' => 'Series',
                'slug' => 'series',
                'description' => 'Ya seas de Chicas Gilmore o Breaking Bad, en esta categoría encontrarás series de todos los tiempos luchando entre ellas por tener la nota media más alta de la categoría.',
                'image' => 'https://via.placeholder.com/640x480.png?text=series',
            ],
            [
                'name' => 'Ciudades',
                'slug' => 'ciudades',
                'description' => '¿Quieres descubrir grandes del mundo?¿O prefieres valorar en las que has estado? Entra y vota por la ciudad más bonita, acogedora o simplemente, por la que más te guste.',
                'image' => 'https://via.placeholder.com/640x480.png?text=ciudades',
            ],
            [
                'name' => 'Marcas',
                'slug' => 'marcas',
                'description' => 'Todas las empresas quieren dinero, pero algunas nos tratan mejor que otras, entra y valora a las marcas más reconocidas mundialmente.',
                'image' => 'https://via.placeholder.com/640x480.png?text=marcas',
            ],
            [
                'name' => 'Politicos',
                'slug' => 'politicos',
                'description' => '¡Por fín un lugar donde calificar políticos! ¿Superará alguno el 5? Cuidado con la sección de comentarios porque promete ser bastante caliente...',
                'image' => 'https://via.placeholder.com/640x480.png?text=politicos',
            ],
            [
                'name' => 'Paises',
                'slug' => 'paises',
                'description' => 'Un atardecer en la Toscana, un desayuno con vistas a la Torre Eiffel, la Estatua de la Libertad iluminada de noche... Imposible decidirse, por eso en Kudos tenemos un ránking para decidir qué país se lleva la palma.',
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
