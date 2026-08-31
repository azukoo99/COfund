<?php

namespace App\Jobs;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DisburseCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Campaign $campaign)
    {
    }

    public function handle(): void
    {
        $campaign = $this->campaign;

        // Jaga-jaga: hanya proses kampanye yang benar status-nya
        if ($campaign->status !== 'success') {
            return;
        }

        DB::transaction(function () use ($campaign) {
            $totalCollected = $campaign->collected_amount;
            $platformFeeRate = 0.05; // 5%
            $platformFee = round($totalCollected * $platformFeeRate, 2);
            $netAmount = $totalCollected - $platformFee;

            // Catat transaksi platform_fee
            $campaign->user->transactions()->create([
                'campaign_id' => $campaign->id,
                'type' => 'platform_fee',
                'amount' => $platformFee,
                'status' => 'success',
                'reference' => 'FEE-' . strtoupper(uniqid()),
            ]);

            // Tambah saldo creator
            $campaign->user->increment('balance', $netAmount);

            // Catat transaksi disbursement
            $campaign->user->transactions()->create([
                'campaign_id' => $campaign->id,
                'type' => 'disbursement',
                'amount' => $netAmount,
                'status' => 'success',
                'reference' => 'DISB-' . strtoupper(uniqid()),
            ]);

            // Kirim notifikasi ke creator (in-app, sesuai modul 8)
            $campaign->user->notifications()->create([
                'type' => 'campaign_disbursed',
                'title' => 'Kampanye berhasil, dana sudah dicairkan',
                'body' => "Kampanye \"{$campaign->title}\" berhasil mencapai target. Dana sebesar Rp {$netAmount} sudah dicairkan ke saldo Anda.",
                'data' => ['campaign_id' => $campaign->id],
            ]);
        });
    }
}
