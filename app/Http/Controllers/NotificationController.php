<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * GET /notifications — Full page list
     */
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);
        $userId = $user->id;

        $notifications = Notification::visibleToUser($user)
            ->with(['reads' => fn ($q) => $q->where('user_id', $userId)])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $unreadCount = Notification::visibleToUser($user)
            ->unreadForUser($userId)
            ->count();

        $items = $notifications->getCollection()->map(fn (Notification $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'message' => $n->message,
            'link' => $n->link,
            'is_unread' => $n->isUnreadForUser($userId),
            'is_deletable' => (bool) $n->user_id,
            'time_ago' => $n->created_at->diffForHumans(),
            'time_full' => $n->created_at->format('d M Y H:i'),
        ])->values();

        return Inertia::render('Notifications', [
            'notifications' => $items,
            'unreadCount' => $unreadCount,
            'total' => $notifications->total(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'prev_url' => $notifications->previousPageUrl(),
                'next_url' => $notifications->nextPageUrl(),
            ],
        ]);
    }

    /**
     * GET /notifications/recent — JSON for bell dropdown
     */
    public function recent(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);
        $userId = $user->id;

        /** @var Collection<int, Notification> $notifications */
        $notifications = Notification::visibleToUser($user)
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

        $unreadCount = Notification::visibleToUser($user)
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
        $user = Auth::user();
        abort_unless($user, 401);
        $userId = $user->id;

        $count = Notification::visibleToUser($user)
            ->unreadForUser($userId)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * POST /notifications/{id}/read — Mark single as read
     */
    public function markRead(int|string $id)
    {
        $user = Auth::user();
        abort_unless($user, 401);
        $userId = $user->id;

        $n = Notification::visibleToUser($user)->findOrFail($id);

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
        $user = Auth::user();
        abort_unless($user, 401);
        $userId = $user->id;

        DB::transaction(function () use ($user, $userId) {
            // Personal notifications: stamp read_at
            Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            // Broadcast notifications: insert pivot rows for those not yet read
            $broadcastIds = Notification::whereNull('user_id')
                ->where('created_at', '>=', $user->created_at)
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
