<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;


//Rutas Públicas
// Categorías
Route::prefix('categories')->group(function () {
	Route::get('/', [CategoryController::class, 'index']);
	Route::get('/{category}', [CategoryController::class, 'show']);
	Route::get('/{category}/ranking', [CategoryController::class, 'ranking']);
});

// Items
Route::prefix('items')->group(function () {
	Route::get('/', [ItemController::class, 'index']); // Todos los items aceptados
});

// Rutas para usuarios autenticados (faltan las asociadas al perfil).
Route::middleware('auth:sanctum')->group(function () {

	// Items - Acciones de usuario
	Route::prefix('items')->group(function () {
		Route::post('/', [ItemController::class, 'store']); // Crear propuesta de item
		Route::get('/my-items', [ItemController::class, 'myItems']); // Mis items creados
		Route::get('/{item}', [ItemController::class, 'show']); // Detalle de un item
		Route::put('/{item}', [ItemController::class, 'update']); // Editar mi item (solo si es pending)
		Route::delete('/{item}', [ItemController::class, 'destroy']); // Eliminar mi item (solo si es pending)
	});

	// Votos - Sistema de votación
	Route::prefix('votes')->group(function () {
		Route::post('/', [VoteController::class, 'store']); // Votar un item
		Route::get('/my-votes', [VoteController::class, 'myVotes']); // Mis votos
		Route::put('/{vote}', [VoteController::class, 'update']); // Actualizar mi voto
		Route::delete('/{vote}', [VoteController::class, 'destroy']); // Eliminar mi voto
	});

	// Rutas de administrador (faltan las asociadas a la gestión de usuarios y tags)
	Route::middleware('admin')->group(function () {

		// Categorías - CRUD completo
		Route::prefix('categories')->group(function () {
			Route::post('/', [CategoryController::class, 'store']); // Guardar nueva categoría
			Route::put('/{category}', [CategoryController::class, 'update']);
			Route::delete('/{category}', [CategoryController::class, 'destroy']);
		});

		// Items - Gestión administrativa
		Route::prefix('admin/items')->group(function () {
			Route::get('/pending', [ItemController::class, 'pending']); // Listar items pendientes de revisión
			Route::patch('/{item}/accept', [ItemController::class, 'accept']); // Aceptar item
			Route::patch('/{item}/reject', [ItemController::class, 'reject']); // Rechazar item
			Route::delete('/{item}/force', [ItemController::class, 'forceDestroy']); // Eliminar por completo cualquier item
		});
	});
});

// Rutas de autenticación
require __DIR__.'/auth.php';