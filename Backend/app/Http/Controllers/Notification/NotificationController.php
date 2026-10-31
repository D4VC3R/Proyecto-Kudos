<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\ListNotificationsRequest;
use App\Http\Requests\Notifications\MarkAllNotificationsReadRequest;
use App\Http\Requests\Notifications\MarkNotificationReadRequest;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;

/**
 * Notificaciones dentro de la app del usuario autenticado (F3-05): listado, marcar una
 * como leída y marcar todas como leídas.
 */
class NotificationController extends Controller
{
    /**
     * Lista paginada de las notificaciones del usuario: primero las no leídas y después por fecha.
     * `meta.unread_count` es el total de no leídas (el contador de la campana).
     */
    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->validated('per_page', 15);

        $notifications = $user->notifications()
            ->reorder() // notifications() trae latest(): hay que quitarlo para poner las no leídas primero
            ->orderByRaw('read_at IS NOT NULL') // false (no leída) antes que true
            ->orderByDesc('created_at')
            ->orderByDesc('id') // desempate estable para la paginación
            ->paginate($perPage);

        return $this->respondList(
            data: NotificationResource::collection($notifications),
            meta: [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        );
    }

    /**
     * Marca como leída una notificación propia. Es idempotente: no cambia el read_at si ya estaba leída.
     * La de otro usuario da 404, igual que un id inexistente (sin route model binding para no exponer las ajenas).
     */
    public function read(MarkNotificationReadRequest $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return $this->respondMutation(
            message: 'Notificación marcada como leída.',
            data: new NotificationResource($notification),
            meta: ['unread_count' => $request->user()->unreadNotifications()->count()],
        );
    }

    /**
     * Marca como leídas todas las notificaciones no leídas del usuario.
     */
    public function readAll(MarkAllNotificationsReadRequest $request): JsonResponse
    {
        $updated = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->respondMutation(
            message: 'Todas las notificaciones están marcadas como leídas.',
            meta: ['updated' => $updated, 'unread_count' => 0],
        );
    }
}
