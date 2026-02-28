<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ItemSeeder extends Seeder
{
	public function run(): void
	{
		$path = storage_path('app/private/items.csv');

		if (!File::exists($path)) {
			$this->command->error("El archivo items.csv no existe en storage/app/private/");
			return;
		}

		// Obtener usuarios no admin
		$users = User::where('role', 'user')->pluck('id')->toArray();

		if (empty($users)) {
			$this->command->error("No hay usuarios disponibles. Ejecuta UserSeeder primero.");
			return;
		}

		// 🔑 CLAVE: Obtener categorías indexadas por slug => UUID
		$categories = Category::pluck('id', 'slug')->toArray();

		if (empty($categories)) {
			$this->command->error("No hay categorías disponibles. Ejecuta CategorySeeder primero.");
			return;
		}

		// DEBUG: Mostrar categorías cargadas
		$this->command->info("📁 Categorías disponibles:");
		foreach ($categories as $slug => $uuid) {
			$this->command->line("   {$slug} => {$uuid}");
		}
		$this->command->newLine();

		$file = fopen($path, 'r');
		$header = fgetcsv($file, 1000, ',');

		$itemsCreated = 0;
		$itemsSkipped = 0;

		while (($row = fgetcsv($file, 1000, ',')) !== false) {
			$data = array_combine($header, $row);

			$categorySlug = trim($data['category_slug']);

			if (!isset($categories[$categorySlug])) {
				$this->command->warn("❌ Slug '{$categorySlug}' no existe. Item '{$data['name']}' omitido.");
				$itemsSkipped++;
				continue;
			}

			$categoryUuid = $categories[$categorySlug];

			try {
				Item::create([
					'id' => Str::uuid(),
					'name' => $data['name'],
					'description' => $data['description'],
					'image' => null,
					'state' => $data['state'],  // ⬅️ Ya viene correcto del CSV
					'locked_at' => $data['state'] === 'rejected' ? now() : null,
					'locked_by_admin_id' => $data['state'] === 'rejected'
						? User::where('role', 'admin')->first()?->id
						: null,
					'vote_avg' => (float) $data['vote_avg'],
					'vote_count' => (int) $data['vote_count'],
					'creator_id' => $users[array_rand($users)],
					'category_id' => $categoryUuid,
					'created_at' => now()->subDays(rand(1, 90)),
					'updated_at' => now(),
				]);

				$itemsCreated++;

			} catch (\Exception $e) {
				$this->command->error("❌ Error al crear '{$data['name']}': " . $e->getMessage());
				$itemsSkipped++;
			}
		}

		fclose($file);

		$this->command->newLine();
		$this->command->info("✅ {$itemsCreated} items creados correctamente.");

		if ($itemsSkipped > 0) {
			$this->command->warn("⚠️  {$itemsSkipped} items omitidos.");
		}

		$this->command->newLine();
		$this->showSummary();
	}

	private function showSummary(): void
	{
		$this->command->info("📊 Resumen por categoría:");
		$this->command->newLine();

		$categories = Category::with(['items' => function($query) {
			$query->select('category_id', 'state')
				->groupBy('category_id', 'state');
		}])->get();

		foreach ($categories as $category) {
			$total = Item::where('category_id', $category->id)->count();
			$approved = Item::where('category_id', $category->id)
				->where('state', 'accepted')
				->count();
			$pending = Item::where('category_id', $category->id)
				->where('state', 'pending')
				->count();

			$this->command->line(sprintf(
				"   %s (%s): %d total | %d aprobados | %d pendientes",
				$category->name,
				$category->slug,
				$total,
				$approved,
				$pending
			));
		}
	}
}