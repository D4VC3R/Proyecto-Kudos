<?php

use App\Http\Middleware\AuthenticateOptionally;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Providers\MediaServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
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
		$middleware->trustProxies(at: '*');

		$middleware->alias([
			'verified' => EnsureEmailIsVerified::class,
			'admin' => EnsureUserIsAdmin::class,
			'auth.optional' => AuthenticateOptionally::class,
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

        // Laravel convierte ModelNotFoundException en NotFoundHttpException antes de llegar a estos
        // callbacks (Handler::prepareException), así que un 404 de modelo se reconoce por la excepción previa.
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                $previous = $e->getPrevious();

                if ($previous instanceof ModelNotFoundException) {
                    $messages = [
                        'Category' => 'La categoría no existe.',
                        'Item' => 'El ítem no existe.',
                        'ItemComment' => 'El comentario no existe.',
                        'ListItem' => 'El ítem no está en la lista.',
                        'Proposal' => 'La propuesta no existe.',
                        'Report' => 'El reporte no existe.',
                        'User' => 'El usuario no existe.',
                        'UserList' => 'La lista no existe.',
                        'Vote' => 'El voto no existe.',
                        'DatabaseNotification' => 'La notificación no existe.',
                    ];

                    return response()->json([
                        'error' => [
                            'code' => 'not_found',
                            'message' => $messages[class_basename($previous->getModel())] ?? 'El recurso no existe.',
                        ],
                    ], 404);
                }

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

            if ($status === 429) {
                // El throttle sin nombre trae el texto en inglés del framework; los demás 429 traen el suyo.
                $message = $e instanceof ThrottleRequestsException || $e->getMessage() === ''
                    ? 'Has hecho demasiadas peticiones. Espera un momento y vuelve a intentarlo.'
                    : $e->getMessage();

                return response()->json([
                    'error' => [
                        'code' => 'too_many_requests',
                        'message' => $message,
                    ],
                ], 429, $e->getHeaders());
            }

            if ($status === 405) {
                return response()->json([
                    'error' => [
                        'code' => 'method_not_allowed',
                        'message' => 'El método HTTP no está permitido en esta ruta.',
                    ],
                ], 405, $e->getHeaders());
            }

            // 404 de una policy (Response::denyAsNotFound): mismo formato que un 404 de modelo.
            if ($status === 404) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'El recurso no existe.',
                    ],
                ], 404);
            }

            return null;
        });

        // Último recurso: cualquier excepción sin respuesta propia sale como 500 con el formato de la API.
        // Fuera de local y testing no se devuelve nada interno (clase, mensaje, fichero ni traza).
        $exceptions->render(function (Throwable $e, $request) {
            if (! $request->expectsJson()
                || $e instanceof HttpResponseException
                || $e instanceof AuthenticationException
                || $e instanceof ValidationException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() !== 500)) {
                return null;
            }

            $error = [
                'code' => 'server_error',
                'message' => 'Ha ocurrido un error inesperado. Inténtalo de nuevo más tarde.',
            ];

            if (app()->environment('local', 'testing')) {
                $error['details'] = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }

            return response()->json(['error' => $error], 500);
        });
    })->create();
