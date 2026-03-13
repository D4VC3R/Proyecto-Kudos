<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		api: __DIR__ . '/../routes/api.php',
		commands: __DIR__ . '/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware): void {
//        $middleware->api(prepend: [
//            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
//        ]);
//
		$middleware->alias([
			'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
			'admin' => EnsureUserIsAdmin::class,
		]);

		// $middleware->statefulApi();

		//
	})
    ->withExceptions(function (Exceptions $exceptions) {
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
                    'message' => 'No tienes permisos para realizar esta acción.',
                    'error' => 'unauthorized'
                ], 403);
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
    })->create();
