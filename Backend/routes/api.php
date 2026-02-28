<?php

use App\Http\Controllers\AdminReviewController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\VoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Rutas publicas
// Muestra todas las categorías disponibles o una en especifico.
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
// Muestra el ranking de items para una categoría específica, ordenados por votos
Route::get('/categories/{category}/ranking', [CategoryController::class, 'ranking']);
// Muestra los detalles de un item específico, incluyendo su categoría, etiquetas y votos.
Route::get('/items/{item}', [ItemController::class, 'show']);

//Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
	// Ver y actualizar el perfil del usuario autenticado
	Route::get('/user/profile', [ProfileController::class, 'show']);
	Route::put('/user/profile', [ProfileController::class, 'update']);
	// Historial de puntos ganados por el usuario
	Route::get('/user/kudos-history', [ProfileController::class, 'kudosHistory']);

	// Para mostrar los items creados por el usuario autenticado
	Route::get('/user/items', [ItemController::class, 'userItems']); 
	// Para la creación y edición de items
	Route::post('/items', [ItemController::class, 'store']);
	Route::put('/items/{item}', [ItemController::class, 'update']);

	// Votaciones
	// Obtener un item aleatorio de una categoría (para votar)
	Route::get('/categories/{category}/random-item', [CategoryController::class, 'random']);
	// Realizar un voto en un item específico.
	Route::post('/items/{item}/vote', [VoteController::class, 'vote']);
	// Recuperar el historial de votos del usuario autenticado, incluyendo los items votados y las categorías correspondientes.
	Route::get('/user/votes', [VoteController::class, 'userVotes']);

	// Rutas de administración
	Route::middleware('admin')->group(function () {
		// Items pendientes de moderación
		Route::get('/admin/items/pending', [ItemController::class, 'pending']);
		// Bloquear, aprobar o rechazar items
    Route::post('/admin/items/{item}/lock', [ItemController::class, 'lock']);
    Route::post('/admin/items/{item}/review', [AdminReviewController::class, 'review']);

		// Para gestionar las categorías y etiquetas.
		Route::post('/categories', [CategoryController::class, 'store']);
		Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
		Route::post('/tags', [TagController::class, 'store']);
		Route::put('/tags/{tag}', [TagController::class, 'update']);
		Route::delete('/tags/{tag}', [TagController::class, 'destroy']);

	});
});

require __DIR__.'/auth.php';