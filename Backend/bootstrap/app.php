<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Providers\MediaServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

/**
 * Configura y crea la aplicación Laravel.
 *
 * @return Application La instancia de la aplicación configurada.
 */

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		api: __DIR__ . '/../routes/api.php',
		commands: __DIR__ . '/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware): void {
		$middleware->alias([
			'verified' => EnsureEmailIsVerified::class,
			'admin' => EnsureUserIsAdmin::class,
      'not_banned' => EnsureUserIsNotBanned::class,
		]);

	})
	->withProviders([
		MediaServiceProvider::class,
	])
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Debes iniciar sesión para acceder a esta funcionalidad.',
                    ],
                ], 401);
            }
        });

        // Error de autorización
        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'No tienes permisos para realizar esta acción.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'No tienes permisos para realizar esta acción.',
                    ],
                ], 403);
            }
        });

        // Error de verificación de email caducada
        $exceptions->render(function (InvalidSignatureException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'invalid_signature',
                        'message' => 'El enlace proporcionado es inválido o ha expirado.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                // Obtener el primer mensaje de error para mostrarlo directamente al usuario en lugar del mensaje genérico.
                $firstError = collect($e->errors())->flatten()->first();
                $message = $firstError ?: 'La solicitud contiene errores de validación.';

                return response()->json([
                    'error' => [
                        'code' => 'validation_error',
                        'message' => $message,
                        'details' => $e->errors(),
                    ],
                ], 422);
            }
        });

        // Error de modelo no encontrado
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                $model = class_basename($e->getModel());

                $messages = [
                    'Category' => 'La categoría no existe.',
                    'Item' => 'El item no existe.',
                    'Vote' => 'El voto no existe.',
                    'User' => 'El usuario no existe.',
                ];

                $message = $messages[$model] ?? 'El recurso no existe.';

                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => $message,
                    ],
                ], 404);
            }
        });

        // Error 404 genérico
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'route_not_found',
                        'message' => 'La ruta solicitada no existe.',
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if (!$request->expectsJson()) {
                return null;
            }

            $status = $e->getStatusCode();

            if ($status === 422) {
                return response()->json([
                    'error' => [
                        'code' => 'validation_error',
                        'message' => $e->getMessage() !== ''
                            ? $e->getMessage()
                            : 'La solicitud contiene errores de validación.',
                    ],
                ], 422);
            }

            if ($status === 409) {
                return response()->json([
                    'error' => [
                        'code' => 'conflict',
                        'message' => $e->getMessage() !== ''
                            ? $e->getMessage()
                            : 'La solicitud entra en conflicto con el estado actual del recurso.',
                    ],
                ], 409);
            }

            if ($status === 403) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'No tienes permisos para realizar esta acción.',
                    ],
                ], 403);
            }

            return null;
        });
    })->create();
