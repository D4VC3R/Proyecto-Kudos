<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
