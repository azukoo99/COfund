<?php

namespace App\Http\Controllers;

use App\Models\Backing;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/creator
     * Statistik, daftar kampanye, dan grafik funding harian untuk Creator.
     */
    public function creatorDashboard(Request $request)
    {
        $user = $request->user();

        // 1. Daftar Kampanye milik Creator
        $campaigns = $user->campaigns()
            ->with(['category', 'images'])
            ->withCount('backings')
            ->latest()
            ->get()
            ->map(function ($campaign) {
                $percentage = $campaign->target_amount > 0
                    ? round(($campaign->collected_amount / $campaign->target_amount) * 100, 2)
                    : 0;
                $daysLeft = max(0, (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($campaign->deadline)->startOfDay(), false));

                $data = $campaign->toArray();
                $data['collected_percentage'] = $percentage;
                $data['days_left'] = $daysLeft;
                return $data;
            });

        // 2. Statistik Ringkasan Creator
        $campaignIds = $user->campaigns()->pluck('id');
        $totalCollected = $user->campaigns()->sum('collected_amount');
        $totalCampaigns = $user->campaigns()->count();
        $activeCampaigns = $user->campaigns()->where('status', 'active')->count();
        $successfulCampaigns = $user->campaigns()->where('status', 'success')->count();
        $totalBackers = Backing::whereIn('campaign_id', $campaignIds)
            ->where('status', 'completed')
            ->count();

        // 3. Grafik Funding Harian (30 hari terakhir)
        $dailyFunding = Backing::whereIn('campaign_id', $campaignIds)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total_amount, COUNT(id) as total_backings')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        return response()->json([
            'summary' => [
                'total_collected' => (float) $totalCollected,
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'successful_campaigns' => $successfulCampaigns,
                'total_backers' => $totalBackers,
                'balance' => (float) $user->balance,
            ],
            'campaigns' => $campaigns,
            'daily_funding_chart' => $dailyFunding,
        ]);
    }

    /**
     * GET /api/dashboard/backer
     * Ringkasan donasi, reward yang didapat, dan riwayat kampanye yang didanai.
     */
    public function backerDashboard(Request $request)
    {
        $user = $request->user();

        // 1. Ringkasan Finansial Backer
        $totalBacked = $user->backings()->where('status', 'completed')->sum('amount');
        $totalRefunded = $user->backings()->where('status', 'refunded')->sum('amount');
        $backedCampaignsCount = $user->backings()->where('status', 'completed')->distinct('campaign_id')->count('campaign_id');

        // 2. Daftar Kampanye yang didanai + Tier Reward yang didapat
        $backings = $user->backings()
            ->with(['campaign.creator', 'campaign.category', 'tier'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'summary' => [
                'total_backed' => (float) $totalBacked,
                'total_refunded' => (float) $totalRefunded,
                'backed_campaigns_count' => $backedCampaignsCount,
                'balance' => (float) $user->balance,
            ],
            'backings' => $backings,
        ]);
    }

    /**
     * POST /api/me/withdraw
     * Tarik saldo virtual user ke rekening bank (Mock withdrawal).
     */
    public function withdraw(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:10000', 'max:' . $user->balance],
            'bank_name' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:100'],
        ]);

        $transaction = DB::transaction(function () use ($user, $validated) {
            $user->decrement('balance', $validated['amount']);

            $tx = $user->transactions()->create([
                'type' => 'disbursement',
                'amount' => $validated['amount'],
                'status' => 'success',
                'reference' => 'WD-' . strtoupper(uniqid()),
            ]);

            // Kirim notifikasi in-app
            $user->notifications()->create([
                'type' => 'withdrawal_success',
                'title' => 'Penarikan Saldo Berhasil',
                'body' => "Penarikan saldo sebesar Rp " . number_format($validated['amount'], 0, ',', '.') . " ke rekening {$validated['bank_name']} ({$validated['account_number']}) berhasil diproses.",
                'data' => [
                    'transaction_id' => $tx->id,
                    'amount' => $validated['amount'],
                    'bank' => $validated['bank_name'],
                ],
            ]);

            return $tx;
        });

        return response()->json([
            'message' => 'Penarikan saldo berhasil diproses.',
            'remaining_balance' => (float) $user->balance,
            'transaction' => $transaction,
        ]);
    }
}
