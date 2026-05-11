<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Return unread count + latest 20 notifications */
    public function index()
    {
        $notifications = AppNotification::latest()
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'message'    => $n->message,
                'icon'       => $n->icon,
                'meta'       => $n->meta,
                'is_read'    => $n->is_read,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        $unread = AppNotification::where('is_read', false)->count();

        return response()->json(['notifications' => $notifications, 'unread' => $unread]);
    }

    /** Mark a single notification as read */
    public function markRead(int $id)
    {
        AppNotification::findOrFail($id)->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    }

    /** Mark all as read */
    public function markAllRead()
    {
        AppNotification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    }

    /** Delete a notification */
    public function destroy(int $id)
    {
        AppNotification::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}

