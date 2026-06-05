<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder para poblar la tabla de usuarios con datos de prueba.
 * Crea 50 usuarios regulares y los administradores del sistema,
 * asignándoles los roles correspondientes.
 */
class UserSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para crear usuarios de prueba.
     *
     * @return void
     */
    public function run(): void
    {
        User::factory()
            ->count(50)
            ->create()
            ->each(fn ($user) => $user->syncRoles(['user']));

        $admins = [
            [
                'name'              => 'kudosAdmin',
                'email'             => 'admin@kudos.com',
                'password'          => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Requetefeo',
                'email'             => 'admin@feo.com',
                'password'          => Hash::make('FeoFeo00'),
                'email_verified_at' => now(),
            ]
        ];

        foreach ($admins as $adminData) {
            $admin = User::factory()->create($adminData);
            $admin->syncRoles(['admin']);
        }
    }
}
