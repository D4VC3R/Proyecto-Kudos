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
			'images' => [
				[
				'path' => fake()->imageUrl(640, 480),
				'disk' => 'public',
				'alt' => null,
				'order' => 0,
					],
			],
			'extra_data' => $this->buildExtraDataForCategory($categorySlug),
			'status' => Item::STATUS_ACTIVE,
			'vote_avg' => 0,
			'vote_count' => 0,
			'creator_id' => User::inRandomOrder()->first()->id,
			'category_id' => $category->id,
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
				'extra_data' => $this->buildExtraDataForCategory($categorySlug),
				'category_id' => $category->id,
			];
		});
	}

	private function buildExtraDataForCategory(string $categorySlug): array
	{
		return match ($categorySlug) {
			'videojuegos' => [
				'developer' => fake()->company(),
				'publisher' => fake()->company(),
				'platforms' => fake()->randomElements(['PC', 'PS5', 'Xbox Series', 'Switch'], fake()->numberBetween(1, 3)),
				'metacritic_score' => fake()->numberBetween(60, 98),
			],
			'peliculas', 'series' => [
				'director' => fake()->name(),
				'release_year' => fake()->numberBetween(1980, 2026),
				'actors' => [fake()->name(), fake()->name(), fake()->name()],
			],
			'ciudades' => [
				'country' => fake()->country(),
				'population' => fake()->numberBetween(100000, 20000000),
				'language' => fake()->languageCode(),
			],
			'paises' => [
				'continent' => fake()->randomElement(['Europa', 'Asia', 'America', 'Africa', 'Oceania']),
				'population' => fake()->numberBetween(500000, 1500000000),
				'religion' => fake()->randomElement(['Cristianismo', 'Islam', 'Hinduismo', 'Budismo', 'Otra']),
			],
			'politicos' => [
				'party' => fake()->company(),
				'age' => fake()->numberBetween(30, 85),
				'position' => fake()->randomElement(['Presidente', 'Ministro', 'Diputado', 'Senador', 'Alcalde']),
			],
			default => [
				'source' => 'factory',
				'notes' => fake()->sentence(),
			],
		};
	}
}
