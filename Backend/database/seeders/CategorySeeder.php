<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
	public function run(): void
	{
		$path = database_path('./../storage/app/private/categories.csv');

		if (!File::exists($path)) {
			$this->command->error("El archivo categories.csv no existe.");
			return;
		}

		$file = fopen($path, 'r');

		$header = fgetcsv($file, 1000, ','); // Leer encabezados

		while (($row = fgetcsv($file, 1000, ',')) !== false) {

			$data = array_combine($header, $row);

			Category::create([
				'id' => Str::uuid(),
				'name' => $data['name'],
				'description' => $data['description'],
				'slug' => $data['slug'] ?? Str::slug($data['name']),
				'image' => $data['image'],
				'created_at' => now(),
				'updated_at' => now(),
			]);
		}

		fclose($file);

		$this->command->info('Categorias importadas correctamente.');
	}
}