<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
/**
 * Seeder para poblar la tabla de usuarios con datos de prueba.
 * Crea 50 usuarios regulares y un usuario administrador, asignándoles los roles correspondientes.
 */
class UserSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para crear usuarios de prueba.
     * Utiliza la fábrica de usuarios para generar 50 usuarios regulares y un usuario administrador.
     * @return void
     */
        public function run(): void
    {
        User::factory()
            ->count(50)
            ->create()->each(function ($user) {
                $user->syncRoles(['user']);
            });

        User::factory()->admin()->create()->syncRoles(['admin']);
    }
}
