<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

/**
 * Seeder para poblar la tabla de permisos con los permisos necesarios para la aplicación.
 * Crea permisos relacionados con categorías, items, usuarios, propuestas y votos.
 */
class PermissionSeeder extends Seeder
{

    /**
     * Ejecuta el seeder para crear los permisos en la base de datos (Spatie).
     * Crea permisos para leer, insertar, actualizar y eliminar categorías, items, usuarios, propuestas y votos.
     *
     * @return void
     */
    public function run(): void
    {
        Permission::create(['name'=>'read categories']);
        Permission::create(['name'=>'insert categories']);
        Permission::create(['name'=>'update categories']);
        Permission::create(['name'=>'delete categories']);

        Permission::create(['name'=>'read items']);
        Permission::create(['name'=>'insert items']);
        Permission::create(['name'=>'update items']);
        Permission::create(['name'=>'delete items']);

        Permission::create(['name'=>'read users']);
        Permission::create(['name'=>'insert users']);
        Permission::create(['name'=>'update users']);
        Permission::create(['name'=>'delete users']);

        Permission::create(['name'=>'read users profile']);
        Permission::create(['name'=>'update users profile']);

        Permission::create(['name'=>'read items proposals']);
        Permission::create(['name'=>'insert items proposals']);
        Permission::create(['name'=>'update items proposals']);
        Permission::create(['name'=>'delete items proposals']);

        Permission::create(['name'=>'read votes']);
        Permission::create(['name'=>'store votes']);
        Permission::create(['name'=>'update votes']);
        Permission::create(['name'=>'delete votes']);

    }
}
