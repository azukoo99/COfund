<?php

namespace App\Jobs;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RefundBackersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Campaign $campaign)
    {
    }

    public function handle(): void
    {
        $campaign = $this->campaign;

        if ($campaign->status !== 'failed') {
            return;
        }

        $completedBackings = $campaign->backings()->where('status', 'completed')->get();

        foreach ($completedBackings as $backing) {
            DB::transaction(function () use ($backing, $campaign) {
                // Tambah saldo backer
                $backing->user->increment('balance', $backing->amount);

                // Catat transaksi refund
                $backing->user->transactions()->create([
                    'backing_id' => $backing->id,
                    'campaign_id' => $campaign->id,
                    'type' => 'refund',
                    'amount' => $backing->amount,
                    'status' => 'success',
                    'reference' => 'REF-' . strtoupper(uniqid()),
                ]);

                // Update status backing
                $backing->update(['status' => 'refunded']);

                // Notifikasi ke backer
                $backing->user->notifications()->create([
                    'type' => 'campaign_failed',
                    'title' => 'Kampanye gagal, dana sudah dikembalikan',
                    'body' => "Kampanye \"{$campaign->title}\" tidak mencapai target. Dana Anda sebesar Rp {$backing->amount} sudah dikembalikan ke saldo Anda.",
                    'data' => ['campaign_id' => $campaign->id],
                ]);
            });
        }
    }
}
