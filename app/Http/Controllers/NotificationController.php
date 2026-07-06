<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * List the current user's notifications, newest first.
     */
    public function index(Request $request): Response
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Notification $notification) => [
                'id' => $notification->id,
                'message' => $notification->message,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ]);

        return Inertia::render('Notifications', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Notification $notification): RedirectResponse
    {
        Gate::authorize('update', $notification);

        $notification->update(['read_at' => now()]);

        return back();
    }

    /**
     * Mark all of the current user's unread notifications as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
