<?php

use App\Http\Controllers\Admin\AdminItemController;
use App\Http\Controllers\Admin\AdminListController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\Item\ItemCommentController;
use App\Http\Controllers\Item\ItemController;
use App\Http\Controllers\Legal\LegalInfoController;
use App\Http\Controllers\Lists\ListController;
use App\Http\Controllers\Lists\ListLikeController;
use App\Http\Controllers\Lists\OwnListController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\Proposal\ProposalController;
use App\Http\Controllers\Recommendation\RecommendationController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\User\DailyRewardController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\PublicProfileController;
use App\Http\Controllers\User\UserRankingController;
use App\Http\Controllers\Vote\VoteController;
use Illuminate\Support\Facades\Route;

/**
 * ----------------
 * RUTAS PÚBLICAS
 * ----------------
 */
// Sesión opcional: con token, los Resources y las policies conocen al usuario; sin él, es un invitado.
Route::middleware('auth.optional')->group(function () {
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::get('/{category}/ranking', [CategoryController::class, 'ranking']);
    });

    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::get('/items/{item}/similar', [ItemController::class, 'similar']);
    Route::get('/items/{item}/comments', [ItemCommentController::class, 'index']);
    Route::get('/users/ranking', [UserRankingController::class, 'index']);
    // Perfil público por nombre de usuario (F3-03). Va después de /users/ranking ("ranking" además está reservado).
    Route::get('/users/{username}', [PublicProfileController::class, 'show'])->where('username', '[A-Za-z0-9_]{1,30}');

    // Portada (F2-09) y listas públicas por slug.
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/lists/{list:slug}', [ListController::class, 'show']);

    // Datos de las páginas legales (F3-07): correo de contacto legal.
    Route::get('/legal', LegalInfoController::class);
});

/**
 * -------------------------------------------
 * RUTAS AUTENTICADAS (Usuarios verificados)
 * -------------------------------------------
 * Requieren token válido, email confirmado y que el usuario no esté baneado.
 */
Route::middleware(['auth:sanctum', 'verified', 'not_banned'])->group(function () {
    Route::get('/categories/{category}/next-item', [CategoryController::class, 'nextItem']);

    // Importación bajo demanda desde /buscar (F1-07): límites con nombre en AppServiceProvider.
    Route::prefix('catalog')->group(function () {
        Route::get('/search', [CatalogController::class, 'search'])->middleware('throttle:catalog-search');
        Route::post('/import', [CatalogController::class, 'import'])->middleware('throttle:catalog-import');
    });

    Route::prefix('profile')->group(function () {
        Route::get('/minimal', [ProfileController::class, 'minimal']);
        Route::get('/statistics', [ProfileController::class, 'statistics']);
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        // Exportación de datos y borrado de la cuenta (F3-08, RGPD). Límite compartido en AppServiceProvider.
        Route::get('/export', [ProfileController::class, 'export'])->middleware('throttle:account');
        Route::delete('/', [ProfileController::class, 'destroy'])->middleware('throttle:account');
        Route::post('/daily-claim', [DailyRewardController::class, 'claim']);
    });

    // Notificaciones dentro de la app (F3-05): solo canal database, sin correo.
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/read-all', [NotificationController::class, 'readAll']);
        Route::post('/{id}/read', [NotificationController::class, 'read'])->whereUuid('id');
    });

    // Listas propias (F3-01). Bajo /profile para no chocar con GET /lists/{slug}, que es pública.
    Route::prefix('profile/lists')->group(function () {
        Route::get('/', [OwnListController::class, 'index']);
        Route::post('/', [OwnListController::class, 'store']);
        Route::get('/{list}', [OwnListController::class, 'show'])->whereUuid('list');
        Route::put('/{list}', [OwnListController::class, 'update'])->whereUuid('list');
        Route::delete('/{list}', [OwnListController::class, 'destroy'])->whereUuid('list');
        Route::post('/{list}/items', [OwnListController::class, 'addItem'])->whereUuid('list');
        Route::put('/{list}/items/order', [OwnListController::class, 'reorder'])->whereUuid('list');
        Route::patch('/{list}/items/{itemId}', [OwnListController::class, 'updateItemNote'])->whereUuid(['list', 'itemId']);
        Route::delete('/{list}/items/{itemId}', [OwnListController::class, 'removeItem'])->whereUuid(['list', 'itemId']);
    });

    // "Me gusta" de listas (F3-01).
    Route::post('/lists/{list}/like', [ListLikeController::class, 'store'])->whereUuid('list');
    Route::delete('/lists/{list}/like', [ListLikeController::class, 'destroy'])->whereUuid('list');

    // Reportes de contenido (F3-04, DSA): límite por usuario y hora en AppServiceProvider.
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:reports');

    // Onboarding (F2-07): ítems populares de las categorías elegidas para el speed-rating.
    Route::get('/onboarding/items', [OnboardingController::class, 'items']);

    // Recomendaciones por contenido (F2-08): "Porque te gustó X" o populares de sus intereses.
    Route::get('/recommendations', [RecommendationController::class, 'index']);

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

/**
 * -----------------------------------------------
 * RUTAS DE ADMINISTRACIÓN (Panel de Control)
 * -----------------------------------------------
 * Protegidas por el middleware 'admin' que verifica el rol del usuario.
 */
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

    // Cola de reportes (F3-04).
    Route::prefix('admin/reports')->group(function () {
        Route::get('/', [AdminReportController::class, 'index']);
        Route::post('/{report}/resolve', [AdminReportController::class, 'resolve'])->whereUuid('report');
    });

    // Listas editoriales (F2-09).
    Route::prefix('admin/lists')->group(function () {
        Route::get('/', [AdminListController::class, 'index']);
        Route::post('/', [AdminListController::class, 'store']);
        Route::get('/{list}', [AdminListController::class, 'show']);
        Route::put('/{list}', [AdminListController::class, 'update']);
        Route::delete('/{list}', [AdminListController::class, 'destroy']);
        Route::post('/{list}/items', [AdminListController::class, 'addItem']);
        Route::put('/{list}/items/order', [AdminListController::class, 'reorder']);
        Route::delete('/{list}/items/{itemId}', [AdminListController::class, 'removeItem'])->whereUuid('itemId');
    });
});

require __DIR__ . '/auth.php';
