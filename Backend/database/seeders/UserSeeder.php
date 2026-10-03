<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeder para poblar la tabla de usuarios con datos de prueba.
 * Crea 50 usuarios regulares y el administrador del sistema,
 * asignándoles los roles correspondientes.
 */
class UserSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para crear usuarios de prueba.
     * Los usuarios de prueba reciben una contraseña aleatoria que nadie conoce, para que no se pueda
     * iniciar sesión con ellos en la demo pública. El administrador se toma de config('kudos.seed_admin').
     *
     * @return void
     * @throws RuntimeException Si no están definidos el email o la contraseña del administrador.
     */
    public function run(): void
    {
        $admin = config('kudos.seed_admin');

        if (blank($admin['email']) || blank($admin['password'])) {
            throw new RuntimeException('Define SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD en el .env antes de ejecutar los seeders.');
        }

        // Un único hash para todos: calcular 50 hashes bcrypt distintos solo haría el seeding más lento.
        $unusablePassword = Hash::make(Str::random(40));

        User::factory()
            ->count(50)
            ->create(['password' => $unusablePassword])
            ->each(fn ($user) => $user->syncRoles(['user']));

        User::factory()
            ->create([
                'name'              => $admin['name'],
                'email'             => $admin['email'],
                'password'          => Hash::make($admin['password']),
                'email_verified_at' => now(),
            ])
            ->syncRoles(['admin']);
    }
}
