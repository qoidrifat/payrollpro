<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * List notifications for the authenticated user.
     * Uses Laravel's native DatabaseNotification model.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->when($request->type, fn($q, $t) => $q->where('type', 'like', "%{$t}%"))
            ->when($request->has('unread'), fn($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn(DatabaseNotification $n) => [
                'id'        => $n->id,
                'type'      => $n->type,
                'title'     => $n->data['title'] ?? 'Notification',
                'body'      => $n->data['body'] ?? '',
                'data'      => $n->data,
                'read_at'   => $n->read_at?->diffForHumans(),
                'is_read'   => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
                'created_at_raw' => $n->created_at->toIso8601String(),
            ]);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters'       => $request->only(['type', 'unread']),
            'unreadCount'   => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        if ($notification->notifiable_id === auth()->id()) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->back()
            ->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(DatabaseNotification $notification): RedirectResponse
    {
        if ($notification->notifiable_id === auth()->id()) {
            $notification->delete();
        }

        return redirect()->back();
    }
}
