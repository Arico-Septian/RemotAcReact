<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        return response()
            ->view('notifications.index', compact('notifications', 'unreadCount', 'userId'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * GET /notifications/recent — JSON for bell dropdown
     */
    public function recent(Request $request)
    {
        $userId = Auth::id();

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Notification> $notifications */
        $notifications = Notification::forUserOrBroadcast($userId)
            ->with(['reads' => fn ($q) => $q->where('user_id', $userId)])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
        $items = $notifications->map(fn (Notification $n) => [
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
    public function markRead(int|string $id)
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

        DB::transaction(function () use ($userId) {
            // Personal notifications: stamp read_at
            Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            // Broadcast notifications: insert pivot rows for those not yet read
            $broadcastIds = Notification::whereNull('user_id')
                ->whereNotExists(function ($q) use ($userId) {
                    $q->from('notification_reads')
                        ->whereColumn('notification_reads.notification_id', 'notifications.id')
                        ->where('notification_reads.user_id', $userId);
                })
                ->pluck('id');

            if ($broadcastIds->isNotEmpty()) {
                $now = now();
                $rows = $broadcastIds->map(fn ($nid) => [
                    'notification_id' => $nid,
                    'user_id' => $userId,
                    'read_at' => $now,
                ])->all();

                NotificationRead::insertOrIgnore($rows);
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /notifications/{id} — Delete one (only personal, not broadcasts)
     */
    public function destroy(int|string $id)
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
