<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creator = User::where('email', 'creator@cofund.com')->first();
        $backer1 = User::where('email', 'backer@cofund.com')->first();
        $campaign = Campaign::where('slug', 'robot-pembersih-sampah-sungai-otomatis')->first();

        if ($creator && $campaign) {
            Notification::firstOrCreate(
                ['user_id' => $creator->id, 'type' => 'campaign_approved'],
                [
                    'title' => 'Kampanye Disetujui!',
                    'body' => "Selamat! Kampanye \"{$campaign->title}\" telah disetujui admin dan mulai aktif menerima backing.",
                    'data' => ['campaign_id' => $campaign->id],
                ]
            );
        }

        if ($backer1 && $campaign) {
            Notification::firstOrCreate(
                ['user_id' => $backer1->id, 'type' => 'backing_success'],
                [
                    'title' => 'Backing Berhasil!',
                    'body' => "Terima kasih atas kontribusi Anda sebesar Rp 250.000 untuk kampanye \"{$campaign->title}\".",
                    'data' => ['campaign_id' => $campaign->id],
                ]
            );
        }
    }
}
