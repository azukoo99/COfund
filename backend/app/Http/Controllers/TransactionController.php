<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * GET /api/me/balance
     * Saldo user + riwayat transaksi, dengan filter tipe & tanggal.
     */
    public function index(Request $request)
    {
        $query = $request->user()->transactions()->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        return response()->json([
            'balance' => $request->user()->balance,
            'transactions' => $query->paginate(10),
        ]);
    }
}
