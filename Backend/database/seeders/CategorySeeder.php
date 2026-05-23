<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Concerns\DownloadsSeedImages;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use DownloadsSeedImages;

    public function run(): void
    {
        $rows = [
            [
                'name' => 'Videojuegos',
                'slug' => 'videojuegos',
                'description' => 'La batalla definitiva por alzarse con el titulo de mejor videojuego de la historia ha comenzado, ¿Qué juego se alzará con la victoria?',
                'image' => 'https://sm.ign.com/t/ign_pk/screenshot/default/top-10-heroes-without-title-image_snt9.1280.png',
            ],
            [
                'name' => 'Álbumes Musicales',
                'slug' => 'albumes-musicales',
                'description' => 'A lo largo de la historia se han compuesto muchos y muy buenos álbumes, entra y aporta tu granito de arena para conocer el mejor álbum de la historia.',
                'image' => 'https://www.rollingstone.com/wp-content/uploads/2018/06/rolling-stone-best-500-albums-jackson-nirvana-pearl-jam-cash-dylan-pink-floyd-974a8046-27a2-4f1a-aabf-ebed7c5af819.jpg',
            ],
            [
                'name' => 'Artistas Musicales',
                'slug' => 'artistas-musicales',
                'description' => 'Ayuda a tu artista favorito a alcanzar la primera posición del ránking. O lleva a ese artista que no soportas hasta la última posición, ¡entra y participa!',
                'image' => 'https://www.rollingstone.com/wp-content/uploads/2020/09/R1344_TOC_Greatest_Albums.jpg',
            ],
            [
                'name' => 'Libros',
                'slug' => 'libros',
                'description' => 'Shakespeare, Cervantes, Dostoyevski... todos ellos compiten por llevar sus obras a lo más alto del ránking, ¿Cuál es tu favorito?.',
                'image' => 'https://www.fundacionaquae.org/wp-content/uploads/2018/12/libros-1024x576.jpg',
            ],
            [
                'name' => 'Películas',
                'slug' => 'peliculas',
                'description' => 'De Ciudadano Kane hasta Barbie pasando por Scarface y Pretty Woman, las mejores películas de la historia compiten en esta categoría para llegar a la primera posición del ránking.',
                'image' => 'https://m.media-amazon.com/images/M/MV5BNjQyZDMzNDgtYmRmZS00Zjg2LWFjZjYtZDFjMDRlZTBjY2FiXkEyXkFqcGc@._V1_QL75_UY281_CR31,0,500,281_.jpg',
            ],
            [
                'name' => 'Series',
                'slug' => 'series',
                'description' => 'Ya seas de Chicas Gilmore o Breaking Bad, en esta categoría encontrarás series de todos los tiempos luchando entre ellas por tener la nota media más alta de la categoría.',
                'image' => 'https://images.bauerhosting.com/empire/2025/12/Best-TV-Shows-US-List.jpg',
            ],
            [
                'name' => 'Ciudades',
                'slug' => 'ciudades',
                'description' => '¿Quieres descubrir grandes ciudades del mundo? ¿O prefieres valorar en las que has estado? Entra y vota por la ciudad más bonita, acogedora o simplemente, por la que más te guste.',
                'image' => 'https://cdn.prod.website-files.com/68b6fc9d9aa4ba6c211c5ada/693a9e6507a551b4d347b62d_68fb7e0a793ab75abbac156a_shutterstock_2468750491%2520(1).jpeg',
            ],
            [
                'name' => 'Políticos',
                'slug' => 'politicos',
                'description' => '¡Por fín un lugar donde calificar políticos! ¿Superará alguno el 5? Cuidado con la sección de comentarios porque promete ser bastante caliente...',
                'image' => 'https://www.publico.es/files/image_horizontal_mobile/uploads/2025/12/23/694ad9bdc2d51.jpeg',
            ],
            [
                'name' => 'Países',
                'slug' => 'paises',
                'description' => 'Un atardecer en la Toscana, un desayuno con vistas a la Torre Eiffel, la Estatua de la Libertad iluminada de noche... Imposible decidirse, por eso en Kudos tenemos un ránking para decidir qué país se lleva la palma.',
                'image' => 'https://imageio.forbes.com/specials-images/imageserve/66316694cd7689830eb2467f/Tokyo-city-in-Japan-best-countries-report/0x0.jpg',
            ],
        ];

        foreach ($rows as $row) {
					$storedData = $this->downloadAndStoreImage($row['image'], $row['slug'], 'categories');
					$imagePath = is_array($storedData) ? ($storedData['path'] ?? $row['image']) : ($storedData ?? $row['image']);
            Category::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'image' => $imagePath,
                ]
            );
        }
    }
}
