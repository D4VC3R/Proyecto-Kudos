<?php

namespace Tests\Concerns;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

/**
 * Helpers para crear usuarios autenticados y sembrar roles y permisos en los tests.
 */
trait InteractsWithUsers
{
    /**
     * Siembra los permisos y los roles 'admin' y 'user' (las rutas de admin y algunas policies los necesitan).
     */
    protected function seedRolesAndPermissions(): void
    {
        if (Role::query()->exists()) {
            return;
        }

        // Sanctum::actingAs() cambia el guard por defecto a 'sanctum'; los roles deben crearse
        // con el guard 'web', como cuando se siembran desde la consola.
        $guard = config('auth.defaults.guard');
        config(['auth.defaults.guard' => 'web']);

        try {
            $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        } finally {
            config(['auth.defaults.guard' => $guard]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Crea un usuario verificado y sin ban y lo autentica con Sanctum.
     * No le asigna rol: el registro de la app tampoco lo hace.
     */
    protected function actingAsUser(array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'is_banned'         => false,
        ], $attrs));

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Crea un usuario con el rol 'admin' y lo autentica con Sanctum.
     */
    protected function actingAsAdmin(array $attrs = []): User
    {
        $this->seedRolesAndPermissions();

        $admin = $this->actingAsUser($attrs);
        $admin->assignRole('admin');

        return $admin;
    }
}
