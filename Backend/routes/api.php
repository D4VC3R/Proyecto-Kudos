<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminItemController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemCommentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\UserRankingController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

// Públicas
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);
    Route::get('/{category}/ranking', [CategoryController::class, 'ranking']);
});

Route::get('/items', [ItemController::class, 'index']);
Route::get('/items/{item}', [ItemController::class, 'show']);
Route::get('/items/{item}/comments', [ItemCommentController::class, 'index']);
Route::get('/users/ranking', [UserRankingController::class, 'index']);

// Autenticadas
Route::middleware(['auth:sanctum', 'verified', 'not_banned'])->group(function () {
    Route::get('/categories/{category}/next-item', [CategoryController::class, 'nextItem']);

    Route::prefix('profile')->group(function () {
        Route::get('/minimal', [ProfileController::class, 'minimal']);
        Route::get('/statistics', [ProfileController::class, 'statistics']);
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
    });

    // Proposals de usuario
    Route::prefix('proposals')->group(function () {
        Route::post('/', [ProposalController::class, 'store']);
        Route::get('/my-proposals', [ProposalController::class, 'myProposals']);
        Route::get('/{proposal}', [ProposalController::class, 'show']);
        Route::put('/{proposal}', [ProposalController::class, 'update']);
        Route::delete('/{proposal}', [ProposalController::class, 'destroy']);
    });

    // Items accesibles a usuario autenticado
    Route::prefix('items')->group(function () {
        Route::get('/my-items', [ItemController::class, 'myItems']);
        Route::post('/{item}/comments', [ItemCommentController::class, 'store']);
    });

    Route::put('/comments/{comment}', [ItemCommentController::class, 'update']);
    Route::delete('/comments/{comment}', [ItemCommentController::class, 'destroy']);

    Route::prefix('votes')->group(function () {
        Route::post('/', [VoteController::class, 'store']);
        Route::get('/my-votes', [VoteController::class, 'myVotes']);
        Route::put('/{vote}', [VoteController::class, 'update']);
        Route::delete('/{vote}', [VoteController::class, 'destroy']);
    });
});

// Admin
Route::middleware(['auth:sanctum', 'verified', 'not_banned', 'admin'])->group(function () {
    Route::prefix('categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });

    // Crear items directos solo admin
    Route::post('/items', [ItemController::class, 'store']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);

    Route::prefix('admin/items')->group(function () {
        Route::get('/', [AdminItemController::class, 'index']);
        Route::put('/{item}', [AdminItemController::class, 'update']);
        Route::patch('/{item}/moderate', [AdminItemController::class, 'moderate']);
    });

    Route::prefix('admin/users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/{user}', [AdminUserController::class, 'show']);
        Route::patch('/{user}/ban', [AdminUserController::class, 'ban']);
        Route::patch('/{user}/unban', [AdminUserController::class, 'unban']);
        Route::post('/{user}/sessions/revoke', [AdminUserController::class, 'revokeTokens']);
    });

    Route::prefix('admin/proposals')->group(function () {
        Route::get('/', [ProposalController::class, 'adminIndex']);
        Route::get('/pending', [ProposalController::class, 'pending']);
        Route::patch('/{proposal}/review', [ProposalController::class, 'review']);
    });

    Route::prefix('admin/comments')->group(function () {
        Route::patch('/{comment}/hide', [ItemCommentController::class, 'hide']);
        Route::patch('/{comment}/unhide', [ItemCommentController::class, 'unhide']);
    });
});

require __DIR__ . '/auth.php';
