<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
	use WithoutModelEvents;

	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		$this->call([
			CategorySeeder::class,  // Primero las categorías
			UserSeeder::class,      // Luego los usuarios
			ItemSeeder::class,      // Después los items
			VoteSeeder::class,      // Finalmente los votos
		]);

		$this->command->info("🎉 Database seeding completado exitosamente!");
	}
}
