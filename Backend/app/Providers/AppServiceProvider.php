<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemComment;
use App\Models\Proposal;
use App\Models\Vote;
use App\Policies\CategoryPolicy;
use App\Policies\ItemPolicy;
use App\Policies\ItemCommentPolicy;
use App\Policies\ProposalPolicy;
use App\Policies\VotePolicy;
use Closure;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios de la aplicación.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Configura las URL de los correos, registra las políticas y garantiza la infraestructura.
     */
    public function boot(): void
    {
        // Autorreparación de Infraestructura (Carpetas de caché y enlaces simbólicos)
        $this->ensureFrameworkDirectoriesExist();
        $this->ensureStorageSymlinkExists();

        // Configuración de Entorno
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Interceptores de Correo Electrónico
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        VerifyEmail::createUrlUsing(function (object $notifiable) {
            $verifyUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return config('app.frontend_url') . '/verify-email?verify_url=' . urlencode($verifyUrl);
        });

        // Registro de Políticas de Autorización
        Gate::policy(Item::class, ItemPolicy::class);
        Gate::policy(ItemComment::class, ItemCommentPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Proposal::class, ProposalPolicy::class);
        Gate::policy(Vote::class, VotePolicy::class);

        $this->configureRateLimiting();
    }

    /**
     * Límites con nombre de la importación bajo demanda (F1-07), por usuario y por minuto.
     * Responden 429 con el formato de error de la API (bootstrap/app.php no da formato a los 429).
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('catalog-search', fn (Request $request) => Limit::perMinute((int) config('catalog.on_demand.search_per_minute', 10))
            ->by((string) ($request->user()?->id ?? $request->ip()))
            ->response($this->tooManyRequests('Has hecho demasiadas búsquedas en el catálogo externo. Espera un minuto y vuelve a intentarlo.')));

        RateLimiter::for('catalog-import', fn (Request $request) => Limit::perMinute((int) config('catalog.on_demand.import_per_minute', 5))
            ->by((string) ($request->user()?->id ?? $request->ip()))
            ->response($this->tooManyRequests('Has añadido demasiados títulos seguidos. Espera un minuto y vuelve a intentarlo.')));
    }

    private function tooManyRequests(string $message): Closure
    {
        return fn (Request $request, array $headers) => response()->json([
            'error' => ['code' => 'too_many_requests', 'message' => $message],
        ], 429, $headers);
    }

    /**
     * Crea los directorios temporales que Git ignora pero que Laravel necesita
     * para compilar vistas (emails) y gestionar cachés o sesiones.
     */
    private function ensureFrameworkDirectoriesExist(): void
    {
        $directories = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    /**
     * Garantiza la existencia y validez del enlace simbólico hacia el volumen dinámico.
     */
    private function ensureStorageSymlinkExists(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $target = storage_path('app/public');
        $link = public_path('storage');

        if (is_link($link) && !file_exists($link)) {
            unlink($link);
        }

        if (!file_exists($link)) {
            if (file_exists($target)) {
                @symlink($target, $link);
            }
        }
    }
}
