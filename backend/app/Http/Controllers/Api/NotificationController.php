<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /** Quante notifiche si mostrano al massimo: le più recenti. */
    private const LIMIT = 50;

    /** Le ultime notifiche del cliente e quante sono ancora da leggere. */
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'data' => $user->notifications()
                ->latest()
                ->orderByDesc('id')   // a parità di secondo: l'ultima creata prima
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (DatabaseNotification $n) => [
                    'id' => $n->id,
                    'kind' => $n->data['kind'] ?? 'service',
                    'title' => $n->data['title'] ?? '',
                    'body' => $n->data['body'] ?? null,
                    'url' => $n->data['url'] ?? null,
                    'read' => $n->read_at !== null,
                    'created_at' => $n->created_at->toIso8601String(),
                ])
                ->values(),
        ]);
    }

    /** Solo il numero di non lette: per il pallino della campanella (richiesta leggerissima). */
    public function unreadCount(Request $request)
    {
        return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markRead(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->first()?->markAsRead();

        return response()->noContent();
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
