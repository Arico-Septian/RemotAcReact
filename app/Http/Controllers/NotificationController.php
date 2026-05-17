<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * GET /notifications — Full page list
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $notifications = Notification::forUserOrBroadcast($userId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = Notification::forUserOrBroadcast($userId)->unread()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * GET /notifications/recent — JSON for bell dropdown
     */
    public function recent(Request $request)
    {
        $userId = Auth::id();

        $items = Notification::forUserOrBroadcast($userId)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'severity' => $n->severity,
                'title' => $n->title,
                'message' => $n->message,
                'link' => $n->link,
                'is_unread' => $n->isUnread(),
                'time_ago' => $n->time_ago,
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        $unreadCount = Notification::forUserOrBroadcast($userId)->unread()->count();

        return response()->json([
            'items' => $items,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * GET /notifications/unread-count — JSON for bell badge
     */
    public function unreadCount()
    {
        $count = Notification::forUserOrBroadcast(Auth::id())->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * POST /notifications/{id}/read — Mark single as read
     */
    public function markRead($id)
    {
        $userId = Auth::id();

        $n = Notification::forUserOrBroadcast($userId)->findOrFail($id);

        if ($n->isUnread()) {
            $n->update(['read_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * POST /notifications/read-all — Mark all as read
     */
    public function markAllRead()
    {
        Notification::forUserOrBroadcast(Auth::id())
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /notifications/{id} — Delete one (only personal, not broadcasts)
     */
    public function destroy($id)
    {
        $userId = Auth::id();

        $n = Notification::where('id', $id)->where('user_id', $userId)->first();

        if ($n) {
            $n->delete();
        }

        return response()->json(['ok' => true]);
    }
}
