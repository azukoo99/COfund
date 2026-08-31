<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * List notifikasi milik user yang login.
     */
    public function index(Request $request)
    {
        $query = $request->user()->notifications()->latest();

        // Filter jika hanya ingin yang belum dibaca
        if ($request->query('unread_only') === 'true') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(15);

        return response()->json($notifications);
    }

    /**
     * GET /api/notifications/unread-count
     * Jumlah notifikasi yang belum dibaca (untuk badge icon lonceng).
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->notifications()->whereNull('read_at')->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * PATCH /api/notifications/{notification}/read
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke notifikasi ini.',
            ], 403);
        }

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Notifikasi telah ditandai sebagai dibaca.',
            'notification' => $notification,
        ]);
    }

    /**
     * PATCH /api/notifications/read-all
     * Tandai seluruh notifikasi user sebagai sudah dibaca.
     */
    public function markAllAsRead(Request $request)
    {
        $updatedCount = $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => "Semua notifikasi ({$updatedCount}) telah ditandai sebagai dibaca.",
        ]);
    }

    /**
     * DELETE /api/notifications/{notification}
     * Hapus notifikasi.
     */
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke notifikasi ini.',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notifikasi berhasil dihapus.',
        ]);
    }
}
