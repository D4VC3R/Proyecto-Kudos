<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

/**
 * Seeder para roles y permisos.
 * Crea los roles 'admin' y 'user', asigna todos los permisos al rol 'admin' y permisos específicos al rol 'user'.
 */
class RoleSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para crear roles y asignar permisos.
     * Crea los roles 'admin' y 'user', luego asigna todos los permisos al rol 'admin' y permisos específicos al rol 'user'.
     */
    public function run(): void
    {
        Role::create(['name'=>'admin']);
        Role::create(['name'=>'user']);

        Role::findByName('admin')->givePermissionTo(Permission::all());
        Role::findByName('user')->givePermissionTo([
            'read categories',
            'read items',
            'read users profile',
            'update users profile',
            'read items proposals',
            'insert items proposals',
            'update items proposals',
            'delete items proposals',
        ]);
    }
}
