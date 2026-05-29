<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para generar datos de prueba de items.
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    // Datos estáticos
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
     * Define el estado predeterminado para el item, seleccionando un nombre y descripción relacionados con la categoría.
     * Si se proporciona una categoría específica en el contexto, se utiliza esa categoría; de lo contrario, se selecciona una al azar.
     * Se asegura de que los items generados sean variados y relacionados con su categoría correspondiente.
     *
     * @return array Los atributos predeterminados para un item.
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
			'images' => [],
			'status' => Item::STATUS_ACTIVE,
			'vote_avg' => 0,
			'vote_count' => 0,
			'creator_id' => User::inRandomOrder()->first()->id,
			'category_id' => $category->id,
		];
	}

    /**
     * Obtiene un item único para una categoría específica, asegurando que no se repitan hasta que se hayan utilizado todos los items disponibles para esa categoría.
     * Si ya se han utilizado todos los items, se reinicia el ciclo para permitir su reutilización.
     * @param string $categorySlug El slug de la categoría para la que se desea obtener un item.
     * @param array $items La lista de items disponibles para esa categoría.
     * @return array Un item único para la categoría especificada.
     */

	private function getUniqueItemForCategory(string $categorySlug, array $items): array
	{
		if (!isset(self::$usedItemsPerCategory[$categorySlug])) {
			self::$usedItemsPerCategory[$categorySlug] = [];
		}

		$availableItems = array_filter($items, function ($item) use ($categorySlug) {
			return !in_array($item['name'], self::$usedItemsPerCategory[$categorySlug]);
		});

		if (empty($availableItems)) {
			self::$usedItemsPerCategory[$categorySlug] = [];
			$availableItems = $items;
		}

		$item = fake()->randomElement($availableItems);

		self::$usedItemsPerCategory[$categorySlug][] = $item['name'];

		return $item;
	}


    /**
     * Define un estado para generar un item específico para una categoría dada, utilizando la función de obtención de items únicos.
     * Esto asegura que los items generados para esa categoría sean variados y relacionados con ella, evitando repeticiones hasta que se hayan utilizado todos los items disponibles.
     *
     * @param Category $category La categoría para la cual se desea generar un item.
     * @return static La instancia del factory con el estado definido para la categoría especificada.
     */
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
