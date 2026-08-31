<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RefundBackersJob;
use App\Models\Campaign;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class CampaignAdminController extends Controller
{
    /**
     * Pastikan hanya admin yang bisa mengakses controller ini.
     */
    private function checkAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(response()->json([
                'message' => 'Hanya admin yang memiliki hak akses untuk tindakan ini.',
            ], 403));
        }
    }

    /**
     * GET /api/admin/overview
     * Statistik platform, total dana terkumpul, platform fee, & grafik bulanan (Modul 10.4).
     */
    public function overview(Request $request)
    {
        $this->checkAdmin($request);

        // 1. Total Kampanye per Status
        $campaignsByStatus = [
            'draft' => Campaign::where('status', 'draft')->count(),
            'review' => Campaign::where('status', 'review')->count(),
            'active' => Campaign::where('status', 'active')->count(),
            'success' => Campaign::where('status', 'success')->count(),
            'failed' => Campaign::where('status', 'failed')->count(),
            'total' => Campaign::count(),
        ];

        // 2. Total Dana & Platform Fee
        $totalCollected = (float) Campaign::whereIn('status', ['active', 'success'])->sum('collected_amount');
        $totalPlatformFee = (float) Transaction::where('type', 'platform_fee')->where('status', 'success')->sum('amount');
        $totalDisbursed = (float) Transaction::where('type', 'disbursement')->where('status', 'success')->sum('amount');
        $totalRefunded = (float) Transaction::where('type', 'refund')->where('status', 'success')->sum('amount');

        // 3. Total User per Role
        $usersByRole = [
            'backer' => User::where('role', 'backer')->count(),
            'creator' => User::where('role', 'creator')->count(),
            'admin' => User::where('role', 'admin')->count(),
            'total' => User::count(),
        ];

        // 4. Grafik Kampanye Baru per Bulan (6 bulan terakhir)
        $monthlyCampaigns = Campaign::where('created_at', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as total")
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();

        return response()->json([
            'total_collected_platform' => $totalCollected,
            'total_platform_fee_collected' => $totalPlatformFee,
            'total_disbursed' => $totalDisbursed,
            'total_refunded' => $totalRefunded,
            'total_campaigns' => $campaignsByStatus['total'],
            'total_users' => $usersByRole['total'],
            'campaigns_by_status' => $campaignsByStatus,
            'financial_summary' => [
                'total_collected_funds' => $totalCollected,
                'total_platform_fee_received' => $totalPlatformFee,
                'total_disbursed' => $totalDisbursed,
                'total_refunded' => $totalRefunded,
            ],
            'users_by_role' => $usersByRole,
            'monthly_campaigns' => $monthlyCampaigns,
            'monthly_campaigns_chart' => $monthlyCampaigns,
        ]);
    }

    /**
     * GET /api/admin/campaigns
     * List kampanye untuk admin, dengan filter status.
     */
    public function index(Request $request)
    {
        $this->checkAdmin($request);

        $query = Campaign::with(['category', 'creator:id,name,email', 'images', 'tiers'])
            ->withCount('backings')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $campaigns = $query->paginate(15);

        return response()->json($campaigns);
    }

    /**
     * GET /api/admin/campaigns/{campaign}
     * Detail kampanye + seluruh riwayat backing untuk admin (Modul 10.2).
     */
    public function show(Request $request, Campaign $campaign)
    {
        $this->checkAdmin($request);

        $campaign->load([
            'category',
            'creator',
            'images',
            'tiers',
            'updates',
            'backings.backer:id,name,email',
            'backings.tier',
            'backings.transactions',
        ])->loadCount('backings');

        return response()->json($campaign);
    }

    /**
     * POST /api/admin/campaigns/{campaign}/approve
     * Admin menyetujui kampanye berstatus review menjadi active (live).
     */
    public function approve(Request $request, Campaign $campaign)
    {
        $this->checkAdmin($request);

        if ($campaign->status !== 'review') {
            return response()->json([
                'message' => 'Hanya kampanye berstatus review yang dapat disetujui.',
            ], 422);
        }

        $campaign->update([
            'status' => 'active',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_note' => null,
        ]);

        // Kirim notifikasi in-app ke creator pemilik kampanye
        $campaign->creator->notifications()->create([
            'type' => 'campaign_approved',
            'title' => 'Kampanye Anda Telah Disetujui!',
            'body' => "Selamat! Kampanye \"{$campaign->title}\" telah disetujui admin dan sekarang sudah aktif menerima backing.",
            'data' => [
                'campaign_id' => $campaign->id,
                'slug' => $campaign->slug,
            ],
        ]);

        return response()->json([
            'message' => 'Kampanye berhasil disetujui dan kini berstatus active.',
            'campaign' => $campaign,
        ]);
    }

    /**
     * POST /api/admin/campaigns/{campaign}/reject
     * Admin menolak kampanye berstatus review kembali menjadi draft dengan catatan.
     */
    public function reject(Request $request, Campaign $campaign)
    {
        $this->checkAdmin($request);

        if ($campaign->status !== 'review') {
            return response()->json([
                'message' => 'Hanya kampanye berstatus review yang dapat ditolak.',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_note' => ['required', 'string', 'max:1000'],
        ]);

        $campaign->update([
            'status' => 'draft',
            'rejection_note' => $validated['rejection_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // Kirim notifikasi in-app ke creator pemilik kampanye
        $campaign->creator->notifications()->create([
            'type' => 'campaign_rejected',
            'title' => 'Kampanye Anda Memerlukan Revisi',
            'body' => "Pengajuan kampanye \"{$campaign->title}\" ditolak dengan catatan: {$validated['rejection_note']}. Silakan perbaiki dan ajukan ulang.",
            'data' => [
                'campaign_id' => $campaign->id,
                'rejection_note' => $validated['rejection_note'],
            ],
        ]);

        return response()->json([
            'message' => 'Kampanye telah ditolak dan dikembalikan ke status draft dengan catatan revisi.',
            'campaign' => $campaign,
        ]);
    }

    /**
     * POST /api/admin/campaigns/{campaign}/force-fail
     * Admin membatalkan secara paksa kampanye aktif untuk kasus darurat (Modul 10.2).
     */
    public function forceFail(Request $request, Campaign $campaign)
    {
        $this->checkAdmin($request);

        if ($campaign->status !== 'active') {
            return response()->json([
                'message' => 'Hanya kampanye berstatus active yang dapat di-force fail.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $campaign->update([
            'status' => 'failed',
            'rejection_note' => 'Dibatalkan oleh Admin: ' . $validated['reason'],
        ]);

        // Dispatch otomatis pengembalian dana ke semua backer via Queue Job (Modul 7.3)
        RefundBackersJob::dispatch($campaign);

        // Notifikasi ke creator
        $campaign->creator->notifications()->create([
            'type' => 'campaign_failed',
            'title' => 'Kampanye Anda Dibatalkan oleh Admin',
            'body' => "Kampanye \"{$campaign->title}\" telah dibatalkan oleh Admin dengan alasan: {$validated['reason']}. Seluruh dana backer telah direfund.",
            'data' => [
                'campaign_id' => $campaign->id,
                'reason' => $validated['reason'],
            ],
        ]);

        return response()->json([
            'message' => 'Kampanye berhasil dibatalkan (force fail) dan proses refund otomatis telah dijalankan.',
            'campaign' => $campaign,
        ]);
    }
}
