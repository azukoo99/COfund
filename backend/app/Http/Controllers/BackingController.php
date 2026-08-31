<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Backing;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class BackingController extends Controller
{
    /**
     * POST /api/campaigns/{campaign}/back
     * Backer melakukan backing ke kampanye (dengan tier atau nominal bebas).
     */
    public function store(Request $request, Campaign $campaign)
    {
        // Business rule 4: creator tidak bisa backing kampanye sendiri
        if ($campaign->user_id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak bisa melakukan backing pada kampanye Anda sendiri.',
            ], 403);
        }

        // Hanya bisa backing kampanye yang aktif
        if ($campaign->status !== 'active') {
            return response()->json([
                'message' => 'Kampanye ini sedang tidak menerima backing.',
            ], 422);
        }

        $validated = $request->validate([
            'tier_id' => ['nullable', 'exists:campaign_tiers,id'],
            'amount' => ['required', 'numeric', 'min:10000'],
        ]);

        $tier = null;

        if (! empty($validated['tier_id'])) {
            $tier = $campaign->tiers()->find($validated['tier_id']);

            if (! $tier) {
                return response()->json(['message' => 'Tier tidak ditemukan di kampanye ini.'], 404);
            }

            if ($tier->quota > 0 && $tier->remaining_quota <= 0) {
                return response()->json(['message' => 'Kuota tier ini sudah habis.'], 422);
            }

            if ($validated['amount'] < $tier->min_amount) {
                return response()->json([
                    'message' => "Nominal minimum untuk tier ini adalah Rp {$tier->min_amount}.",
                ], 422);
            }
        }

        // Transaksi DB: backing + payment + update collected_amount + kuota tier harus atomic
        $user = $request->user();
        $backing = DB::transaction(function () use ($user, $campaign, $tier, $validated) {
            $backing = Backing::create([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'tier_id' => $tier?->id,
                'amount' => $validated['amount'],
                'status' => 'pending',
            ]);

            // --- Simulasi mock payment gateway ---
            $paymentSuccess = true; // di dunia nyata: panggil payment gateway di sini

            if ($paymentSuccess) {
                $backing->update(['status' => 'completed']);

                $campaign->increment('collected_amount', $validated['amount']);

                if ($tier && $tier->quota > 0) {
                    $tier->decrement('remaining_quota');
                }

                $user->transactions()->create([
                    'backing_id' => $backing->id,
                    'campaign_id' => $campaign->id,
                    'type' => 'payment',
                    'amount' => $validated['amount'],
                    'status' => 'success',
                    'reference' => 'PAY-' . strtoupper(uniqid()),
                ]);

                // Kirim notifikasi in-app ke backer (Alur 5.2.6 & Modul 8.3)
                $user->notifications()->create([
                    'type' => 'backing_success',
                    'title' => 'Backing Berhasil!',
                    'body' => "Terima kasih! Backing Anda sebesar Rp " . number_format($validated['amount'], 0, ',', '.') . " pada kampanye '{$campaign->title}' telah berhasil.",
                    'data' => [
                        'campaign_id' => $campaign->id,
                        'backing_id' => $backing->id,
                        'amount' => $validated['amount'],
                    ],
                ]);

                // Kirim notifikasi in-app ke creator pemilik kampanye (Modul 8.3)
                $campaign->creator->notifications()->create([
                    'type' => 'new_backing',
                    'title' => 'Ada Backing Baru Masuk!',
                    'body' => "{$user->name} telah melakukan backing sebesar Rp " . number_format($validated['amount'], 0, ',', '.') . " pada kampanye '{$campaign->title}'.",
                    'data' => [
                        'campaign_id' => $campaign->id,
                        'backing_id' => $backing->id,
                        'backer_name' => $user->name,
                        'amount' => $validated['amount'],
                    ],
                ]);
            }

            return $backing;
        });

        return response()->json([
            'message' => 'Backing berhasil! Dana Anda sudah masuk ke escrow kampanye.',
            'backing' => $backing->load('tier'),
        ], 201);
    }

    /**
     * GET /api/my-backings
     * Riwayat backing milik user yang login.
     */
    public function myBackings(Request $request)
    {
        $backings = $request->user()
            ->backings()
            ->with(['campaign', 'tier'])
            ->latest()
            ->paginate(10);

        return response()->json($backings);
    }
}
