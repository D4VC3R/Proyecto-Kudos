<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder principal que orquesta la ejecución de los seeders específicos.
 * Orden: categorías, permisos, roles, usuarios y, solo en local, votos de prueba. No siembra ítems ni
 * propuestas: el catálogo lo importa catalog:fetch-new (F1-10).
 */

class DatabaseSeeder extends Seeder
{
	use WithoutModelEvents;

    /**
     * Ejecuta los seeders en el orden correcto: categorías, permisos, roles, usuarios y votos (solo en local).
     * No crea ítems ni propuestas; la secuencia de desarrollo está en docs/infra/entorno-local.md §3.
     * Al finalizar, muestra un mensaje indicando que el seeding ha sido completado.
     * @return void
     */
	public function run(): void
	{
		$seeders = [
			CategorySeeder::class,  // Primero las categorías
			PermissionSeeder::class,
			RoleSeeder::class,
			UserSeeder::class,      // Luego los usuarios
		];

		// El catálogo ya no se siembra: lo importa catalog:fetch-new (F1-10). ItemSeeder y ProposalSeeder
		// son heredados. Los votos de prueba, solo en local: en producción los votos son de usuarios reales.
		if (app()->isLocal()) {
			$seeders[] = VoteSeeder::class;
		}

		$this->call($seeders);

		$this->command->info("Seeding completado.");
	}
}
