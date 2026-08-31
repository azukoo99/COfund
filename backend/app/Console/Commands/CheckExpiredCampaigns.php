<?php

namespace App\Console\Commands;

use App\Jobs\DisburseCampaignJob;
use App\Jobs\RefundBackersJob;
use App\Models\Campaign;
use Illuminate\Console\Command;

class CheckExpiredCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek kampanye yang telah melewati deadline dan jalankan pencairan (success) atau refund (failed)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->toDateString();

        $expiredCampaigns = Campaign::where('status', 'active')
            ->where('deadline', '<', $today)
            ->get();

        $this->info("Menemukan {$expiredCampaigns->count()} kampanye aktif yang melewati deadline.");

        foreach ($expiredCampaigns as $campaign) {
            if ($campaign->collected_amount >= $campaign->target_amount) {
                // Target tercapai -> SUCCESS
                $campaign->update(['status' => 'success']);
                DisburseCampaignJob::dispatch($campaign);
                $this->info("Kampanye #{$campaign->id} ({$campaign->title}) SUKSES. DisburseCampaignJob telah dijalankan.");
            } else {
                // Target tidak tercapai -> FAILED
                $campaign->update(['status' => 'failed']);
                RefundBackersJob::dispatch($campaign);
                $this->warn("Kampanye #{$campaign->id} ({$campaign->title}) GAGAL. RefundBackersJob telah dijalankan.");
            }
        }

        return Command::SUCCESS;
    }
}
