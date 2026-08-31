<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;

class NotifyDeadlineApproaching extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:notify-deadline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi pengingat ke backer untuk kampanye aktif yang mendekati deadline (H-3 dan H-1)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $h3 = now()->addDays(3)->toDateString();
        $h1 = now()->addDays(1)->toDateString();

        $campaigns = Campaign::where('status', 'active')
            ->whereIn('deadline', [$h3, $h1])
            ->get();

        $this->info("Menemukan {$campaigns->count()} kampanye mendekati deadline.");

        foreach ($campaigns as $campaign) {
            $daysLeft = now()->diffInDays($campaign->deadline, false);
            $dayText = $daysLeft == 1 ? "1 hari" : "3 hari";

            // Ambil semua backer unik dari kampanye ini
            $backers = $campaign->backings()
                ->where('status', 'completed')
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter()
                ->unique('id');

            foreach ($backers as $backer) {
                $backer->notifications()->create([
                    'type' => 'deadline_approaching',
                    'title' => "Pengingat Deadline Kampanye (H-{$daysLeft})",
                    'body' => "Kampanye \"{$campaign->title}\" yang Anda ikuti akan berakhir dalam {$dayText} lagi!",
                    'data' => [
                        'campaign_id' => $campaign->id,
                        'days_left' => $daysLeft,
                        'deadline' => $campaign->deadline->toDateString(),
                    ],
                ]);
            }

            $this->info("Notifikasi dikirim ke {$backers->count()} backer untuk kampanye #{$campaign->id}.");
        }

        return Command::SUCCESS;
    }
}
