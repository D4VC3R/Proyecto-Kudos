<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder principal que orquesta la ejecución de todos los seeders específicos.
 * Asegura que se ejecuten en el orden correcto para mantener la integridad referencial.
 */

class DatabaseSeeder extends Seeder
{
	use WithoutModelEvents;

    /**
     * Ejecuta los seeders en el orden correcto para poblar la base de datos con datos de prueba.
     * Primero se crean las categorías, luego los permisos, roles y usuarios, seguidos de los ítems, propuestas y finalmente los votos.
     * Al finalizar, muestra un mensaje indicando que el seeding ha sido completado.
     * @return void
     */
	public function run(): void
	{
		$this->call([
			CategorySeeder::class,  // Primero las categorías
			PermissionSeeder::class,
			RoleSeeder::class,
			UserSeeder::class,      // Luego los usuarios
			ItemSeeder::class,      // Items base del sistema
			ProposalSeeder::class,  // Simulación de moderación de propuestas
			VoteSeeder::class,      // Finalmente los votos
		]);

		$this->command->info("Seeding completado.");
	}
}
