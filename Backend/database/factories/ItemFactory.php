<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    private static array $usedItemsPerCategory = [];
    private static array $itemsByCategory = [
        'videojuegos' => [
            ['name' => 'The Legend of Zelda: Breath of the Wild', 'description' => 'Aventura épica en mundo abierto con mecánicas innovadoras'],
            ['name' => 'Red Dead Redemption 2', 'description' => 'Western con narrativa impresionante y mundo vivo'],
            ['name' => 'The Witcher 3: Wild Hunt', 'description' => 'RPG de fantasía oscura con decisiones morales complejas'],
            ['name' => 'God of War', 'description' => 'Reimaginación de la saga con mitología nórdica'],
            ['name' => 'Elden Ring', 'description' => 'Souls-like en mundo abierto con lore de George R.R. Martin'],
        ],
        'musica' => [
            ['name' => 'Bohemian Rhapsody - Queen', 'description' => 'Obra maestra del rock que desafía géneros musicales'],
            ['name' => 'Stairway to Heaven - Led Zeppelin', 'description' => 'Épica canción que define el rock clásico'],
            ['name' => 'Imagine - John Lennon', 'description' => 'Himno pacifista con mensaje universal de esperanza'],
            ['name' => 'Thriller - Michael Jackson', 'description' => 'Revolución del pop con videoclip icónico'],
            ['name' => 'Smells Like Teen Spirit - Nirvana', 'description' => 'Himno generacional del movimiento grunge'],
        ],
        'peliculas' => [
            ['name' => 'El Padrino', 'description' => 'Obra maestra del cine sobre la mafia italiana en América'],
            ['name' => 'Pulp Fiction', 'description' => 'Narrativa no lineal que revolucionó el cine independiente'],
            ['name' => 'Cadena Perpetua', 'description' => 'Historia de esperanza y amistad en prisión'],
            ['name' => 'El Caballero de la Noche', 'description' => 'Redefinición del género de superhéroes con actuación legendaria'],
            ['name' => 'Forrest Gump', 'description' => 'Viaje emocional a través de décadas de historia americana'],
        ],
        'series' => [
            ['name' => 'Breaking Bad', 'description' => 'Transformación de profesor a narcotraficante con actuaciones magistrales'],
            ['name' => 'Game of Thrones', 'description' => 'Épica fantasía medieval con intrigas políticas y dragones'],
            ['name' => 'The Wire', 'description' => 'Retrato realista del sistema institucional de Baltimore'],
            ['name' => 'Los Soprano', 'description' => 'Pionera en mostrar la complejidad psicológica de un mafioso'],
            ['name' => 'Stranger Things', 'description' => 'Nostalgia ochentera con misterio sobrenatural y amistad'],
        ],
        'ciudades' => [
            ['name' => 'París', 'description' => 'Capital del arte, la moda y la cultura europea'],
            ['name' => 'Tokio', 'description' => 'Fusión perfecta entre tradición milenaria y tecnología futurista'],
            ['name' => 'Nueva York', 'description' => 'Metrópolis multicultural y centro financiero mundial'],
            ['name' => 'Barcelona', 'description' => 'Arquitectura modernista y vida mediterránea vibrante'],
            ['name' => 'Estambul', 'description' => 'Puente entre Europa y Asia con historia milenaria'],
        ],
        'marcas' => [
            ['name' => 'Apple', 'description' => 'Innovación en tecnología y diseño que cambió la industria'],
            ['name' => 'Nike', 'description' => 'Líder en deportes que inspira a atletas de todo el mundo'],
            ['name' => 'Coca-Cola', 'description' => 'Marca icónica que define la cultura del refresco global'],
            ['name' => 'Google', 'description' => 'Gigante tecnológico que organiza la información mundial'],
            ['name' => 'Disney', 'description' => 'Imperio del entretenimiento que crea magia generacional'],
        ],
        'politicos' => [
            ['name' => 'Nelson Mandela', 'description' => 'Líder que luchó contra el apartheid y promovió la reconciliación'],
            ['name' => 'Winston Churchill', 'description' => 'Primer ministro británico que lideró durante la Segunda Guerra Mundial'],
            ['name' => 'Mahatma Gandhi', 'description' => 'Activista de la no violencia que liberó a India del colonialismo'],
            ['name' => 'Abraham Lincoln', 'description' => 'Presidente que abolió la esclavitud en Estados Unidos'],
            ['name' => 'Angela Merkel', 'description' => 'Canciller alemana que lideró Europa durante crisis globales'],
        ],
        'paises' => [
            ['name' => 'Japón', 'description' => 'Nación insular que combina tradición milenaria con innovación tecnológica'],
            ['name' => 'Italia', 'description' => 'Cuna del Renacimiento con patrimonio cultural incomparable'],
            ['name' => 'Noruega', 'description' => 'País nórdico con fiordos espectaculares y alta calidad de vida'],
            ['name' => 'Nueva Zelanda', 'description' => 'Paraíso natural con paisajes de película épica'],
            ['name' => 'Canadá', 'description' => 'Vasta naturaleza salvaje y sociedad multicultural inclusiva'],
        ],
    ];

    private static array $pendingItemsByCategory = [
        'videojuegos' => [
            ['name' => 'Cyberpunk 2077', 'description' => 'RPG futurista en ciudad distópica con modificaciones cibernéticas'],
            ['name' => 'Minecraft', 'description' => 'Creatividad infinita en mundo de bloques procedurales'],
        ],
        'musica' => [
            ['name' => 'Hotel California - Eagles', 'description' => 'Clásico del rock con guitarra icónica'],
            ['name' => 'Like a Rolling Stone - Bob Dylan', 'description' => 'Revolución del folk rock con letras poéticas'],
        ],
        'peliculas' => [
            ['name' => 'Inception', 'description' => 'Thriller psicológico sobre sueños dentro de sueños'],
            ['name' => 'Interstellar', 'description' => 'Odisea espacial sobre amor y supervivencia humana'],
        ],
        'series' => [
            ['name' => 'The Mandalorian', 'description' => 'Western espacial del universo Star Wars'],
            ['name' => 'The Crown', 'description' => 'Drama histórico sobre la monarquía británica'],
        ],
        'ciudades' => [
            ['name' => 'Ámsterdam', 'description' => 'Canales pintorescos y cultura liberal europea'],
            ['name' => 'Dubái', 'description' => 'Modernidad extrema en medio del desierto'],
        ],
        'marcas' => [
            ['name' => 'Tesla', 'description' => 'Revolución eléctrica en la industria automotriz'],
            ['name' => 'Netflix', 'description' => 'Streaming que cambió el consumo de entretenimiento'],
        ],
        'politicos' => [
            ['name' => 'Margaret Thatcher', 'description' => 'Primera ministra británica conocida como la Dama de Hierro'],
            ['name' => 'Franklin D. Roosevelt', 'description' => 'Presidente que lideró USA durante la Gran Depresión'],
        ],
        'paises' => [
            ['name' => 'Islandia', 'description' => 'Isla volcánica con auroras boreales y glaciares'],
            ['name' => 'Costa Rica', 'description' => 'Biodiversidad tropical sin ejército'],
        ],
    ];
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = isset($this->context['category_id'])
            ? Category::find($this->context['category_id'])
            : Category::inRandomOrder()->first();

        $categorySlug = $category->slug;

        $items = self::$itemsByCategory[$categorySlug] ?? [
            ['name' => fake()->words(3, true), 'description' => fake()->sentence(12)]
        ];

        $item = fake()->randomElement($items);

        return [
            'name' => $item['name'],
            'description' => $item['description'],
            'image' => fake()->imageUrl(640, 480),
            'state' => Item::STATE_ACCEPTED,
            'vote_avg' => 0,
            'vote_count' => 0,
            'creator_id' => User::inRandomOrder()->first()->id,
            'category_id' => $category->id,
            'locked_at' => null,
            'locked_by_admin_id' => User::role('admin')->inRandomOrder()->first()?->id,
        ];
    }

    private function getUniqueItemForCategory(string $categorySlug, array $items): array
    {


        // Inicializar el tracking si no existe
        if (!isset(self::$usedItemsPerCategory[$categorySlug])) {
            self::$usedItemsPerCategory[$categorySlug] = [];
        }

        // Obtener items disponibles (no usados)
        $availableItems = array_filter($items, function ($item) use ($categorySlug) {
            return !in_array($item['name'], self::$usedItemsPerCategory[$categorySlug]);
        });

        // Si no quedan items disponibles, resetear y usar todos
        if (empty($availableItems)) {
            self::$usedItemsPerCategory[$categorySlug] = [];
            $availableItems = $items;
        }

        // Seleccionar un item disponible
        $item = fake()->randomElement($availableItems);

        // Marcar como usado
        self::$usedItemsPerCategory[$categorySlug][] = $item['name'];

        return $item;
    }

    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            $category = Category::find($attributes['category_id']);
            $categorySlug = $category->slug;

            $pendingItems = self::$pendingItemsByCategory[$categorySlug] ?? [
                ['name' => fake()->words(3, true), 'description' => fake()->sentence(12)]
            ];

            $item = $this->getUniqueItemForCategory(
                $categorySlug,
                $pendingItems,
            );

            return [
                'name' => $item['name'],
                'description' => $item['description'],
                'state' => Item::STATE_PENDING,
                'locked_at' => null,
                'locked_by_admin_id' => null,
            ];
        });
    }

    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'state' => Item::STATE_REJECTED,
            'locked_at' => now(),
            'locked_by_admin_id' => User::where('role', 'admin')->inRandomOrder()->first()?->id,
        ]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(function () use ($category) {
            $categorySlug = $category->slug;

            $allItems = self::$itemsByCategory[$categorySlug] ?? [
                ['name' => fake()->words(3, true), 'description' => fake()->sentence(12)]
            ];

            $item = $this->getUniqueItemForCategory($categorySlug, $allItems);

            return [
                'name' => $item['name'],
                'description' => $item['description'],
                'category_id' => $category->id,
            ];
        });
    }
}
