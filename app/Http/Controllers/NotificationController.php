<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRead;
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
            ->with(['reads' => fn ($q) => $q->where('user_id', $userId)])
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = Notification::forUserOrBroadcast($userId)
            ->unreadForUser($userId)
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount', 'userId'));
    }

    /**
     * GET /notifications/recent — JSON for bell dropdown
     */
    public function recent(Request $request)
    {
        $userId = Auth::id();

        $items = Notification::forUserOrBroadcast($userId)
            ->with(['reads' => fn ($q) => $q->where('user_id', $userId)])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'severity' => $n->severity,
                'title' => $n->title,
                'message' => $n->message,
                'link' => $n->link,
                'is_unread' => $n->isUnreadForUser($userId),
                'time_ago' => $n->time_ago,
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        $unreadCount = Notification::forUserOrBroadcast($userId)
            ->unreadForUser($userId)
            ->count();

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
        $userId = Auth::id();

        $count = Notification::forUserOrBroadcast($userId)
            ->unreadForUser($userId)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * POST /notifications/{id}/read — Mark single as read
     */
    public function markRead($id)
    {
        $userId = Auth::id();

        $n = Notification::forUserOrBroadcast($userId)->findOrFail($id);

        if ($n->user_id === null) {
            // Broadcast — upsert agar aman dari race condition double-click
            NotificationRead::updateOrInsert(
                ['notification_id' => $n->id, 'user_id' => $userId],
                ['read_at' => now()]
            );
        } elseif ($n->isUnread()) {
            $n->update(['read_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * POST /notifications/read-all — Mark all as read
     */
    public function markAllRead()
    {
        $userId = Auth::id();

        // Personal notifications: stamp read_at
        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Broadcast notifications: insert pivot rows for those not yet read
        $alreadyReadIds = NotificationRead::where('user_id', $userId)
            ->pluck('notification_id');

        $broadcastIds = Notification::whereNull('user_id')
            ->whereNotIn('id', $alreadyReadIds)
            ->pluck('id');

        if ($broadcastIds->isNotEmpty()) {
            $now = now();
            $rows = $broadcastIds->map(fn ($nid) => [
                'notification_id' => $nid,
                'user_id' => $userId,
                'read_at' => $now,
            ])->all();

            // insertOrIgnore aman dari race condition bila dipanggil 2x bersamaan
            NotificationRead::insertOrIgnore($rows);
        }

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

            return response()->json(['ok' => true]);
        }

        // Broadcast (user_id = null) or another user's notification — cannot delete
        $isBroadcast = Notification::where('id', $id)->whereNull('user_id')->exists();

        return response()->json([
            'ok' => false,
            'reason' => $isBroadcast ? 'broadcast' : 'not_found',
        ], 200);
    }
}
