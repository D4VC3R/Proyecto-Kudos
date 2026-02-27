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

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}/ranking', [CategoryController::class, 'ranking']);
Route::get('/items/{item}', [ItemController::class, 'show']);
Route::get('/tags', [TagController::class, 'index']);

//Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
	Route::get('user/profile', [ProfileController::class, 'show']);
	Route::put('user/profile', [ProfileController::class, 'update']);

	Route::post('/items', [ItemController::class, 'store']);

	Route::get('categories/{category}/random-item', [CategoryController::class, 'random']);
	Route::post('/items/{item}/vote', [VoteController::class, 'vote']);

	// Rutas de administración
	Route::middleware('admin')->group(function () {
		Route::get('/moderation', [AdminReviewController::class, 'index']);
		Route::post('/moderation/{item}/start', [AdminReviewController::class, 'startReview']);
		Route::post('/moderation/{item}/approve', [AdminReviewController::class, 'approve']);
		Route::post('/moderation/{item}/reject', [AdminReviewController::class, 'reject']);

		Route::post('/categories', [CategoryController::class, 'store']);
		Route::post('/tags', [TagController::class, 'store']);

	});
});

require __DIR__.'/auth.php';