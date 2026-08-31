<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    private function checkAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(response()->json([
                'message' => 'Hanya admin yang memiliki hak akses untuk tindakan ini.',
            ], 403));
        }
    }

    /**
     * GET /api/admin/users
     * List user dengan filter role, status suspend, dan pencarian nama/email.
     */
    public function index(Request $request)
    {
        $this->checkAdmin($request);

        $query = User::latest();

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('is_suspended')) {
            $query->where('is_suspended', filter_var($request->query('is_suspended'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15);

        return response()->json($users);
    }

    /**
     * GET /api/admin/users/{user}
     * Detail user + seluruh riwayat kampanye, backing, dan transaksi.
     */
    public function show(Request $request, User $user)
    {
        $this->checkAdmin($request);

        $user->load([
            'campaigns' => fn($q) => $q->latest(),
            'backings' => fn($q) => $q->with('campaign:id,title,slug')->latest(),
            'transactions' => fn($q) => $q->latest(),
        ]);

        return response()->json($user);
    }

    /**
     * PATCH /api/admin/users/{user}/suspend
     * Suspend atau aktifkan kembali akun user.
     */
    public function toggleSuspend(Request $request, User $user)
    {
        $this->checkAdmin($request);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak bisa menangguhkan (suspend) akun Anda sendiri.',
            ], 422);
        }

        $newStatus = !$user->is_suspended;
        $user->update([
            'is_suspended' => $newStatus,
            'suspended_at' => $newStatus ? now() : null,
        ]);

        // Jika disuspend, cabut seluruh token login aktif milik user tersebut
        if ($newStatus) {
            $user->tokens()->delete();
        }

        $statusText = $newStatus ? 'ditangguhkan (suspend)' : 'diaktifkan kembali';

        return response()->json([
            'message' => "Akun {$user->name} berhasil {$statusText}.",
            'user' => $user,
        ]);
    }
}
