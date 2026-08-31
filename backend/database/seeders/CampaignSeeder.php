<?php

namespace Database\Seeders;

use App\Models\Backing;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\CampaignTier;
use App\Models\CampaignUpdate;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@cofund.com')->first();
        $creator = User::where('email', 'creator@cofund.com')->first();
        $backer1 = User::where('email', 'backer@cofund.com')->first();
        $backer2 = User::where('email', 'budi@example.com')->first();

        $catTech = Category::where('slug', 'teknologi-inovasi')->first();
        $catEdu = Category::where('slug', 'pendidikan')->first();
        $catArt = Category::where('slug', 'seni-kreatif')->first();
        $catEnv = Category::where('slug', 'lingkungan-hidup')->first();

        // ==========================================
        // 1. CAMPAIGN ACTIVE (Live & Menerima Backing)
        // ==========================================
        $campaignActive = Campaign::firstOrCreate(
            ['slug' => 'robot-pembersih-sampah-sungai-otomatis'],
            [
                'user_id' => $creator->id,
                'category_id' => $catTech->id,
                'title' => 'Robot Pembersih Sampah Sungai Otomatis',
                'slug' => 'robot-pembersih-sampah-sungai-otomatis',
                'description' => 'Inovasi kapal mini robot bertenaga surya dan IoT yang mampu menjaring dan membersihkan sampah plastik terapung di sungai secara otomatis tanpa mencemari lingkungan.',
                'target_amount' => 10000000.00,
                'collected_amount' => 3500000.00,
                'deadline' => now()->addDays(25)->toDateString(),
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'status' => 'active',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subDays(5),
            ]
        );

        CampaignImage::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'url' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800'],
            ['is_primary' => true]
        );
        CampaignImage::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'url' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800'],
            ['is_primary' => false]
        );

        $tier1 = CampaignTier::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'name' => 'Early Supporter'],
            [
                'min_amount' => 50000.00,
                'quota' => 50,
                'remaining_quota' => 45,
                'reward_description' => 'Stiker eksklusif CoFund & sertifikat digital donatur',
            ]
        );
        $tier2 = CampaignTier::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'name' => 'Prototyper Kit'],
            [
                'min_amount' => 250000.00,
                'quota' => 20,
                'remaining_quota' => 18,
                'reward_description' => 'Kaos eksklusif proyek, stiker, dan akses webinar live demo',
            ]
        );
        $tier3 = CampaignTier::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'name' => 'VIP Patron'],
            [
                'min_amount' => 1000000.00,
                'quota' => 5,
                'remaining_quota' => 3,
                'reward_description' => 'Nama diukir di bodi robot, kaos, plakat penghargaan, dan undangan peluncuran',
            ]
        );
        $tierSoldOut = CampaignTier::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'name' => 'Super Early Bird (Sold Out)'],
            [
                'min_amount' => 30000.00,
                'quota' => 10,
                'remaining_quota' => 0,
                'reward_description' => 'Diskon donasi terbatas untuk 10 orang pertama (Habis Terjual)',
            ]
        );

        CampaignUpdate::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'title' => 'Uji Coba Sensor Ultrasonik & Motor Baling-Baling'],
            [
                'content' => 'Halo para backer! Hari ini kami berhasil menyelesaikan perakitan sensor deteksi halangan dan motor kapal dengan daya tahan baterai 6 jam nonstop.',
            ]
        );

        $backing1 = Backing::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'user_id' => $backer1->id],
            [
                'tier_id' => $tier2->id,
                'amount' => 250000.00,
                'status' => 'completed',
            ]
        );
        Transaction::firstOrCreate(
            ['backing_id' => $backing1->id, 'type' => 'payment'],
            [
                'user_id' => $backer1->id,
                'campaign_id' => $campaignActive->id,
                'amount' => 250000.00,
                'status' => 'success',
                'reference' => 'PAY-BACKER1-001',
            ]
        );

        $backing2 = Backing::firstOrCreate(
            ['campaign_id' => $campaignActive->id, 'user_id' => $backer2->id],
            [
                'tier_id' => $tier3->id,
                'amount' => 1000000.00,
                'status' => 'completed',
            ]
        );
        Transaction::firstOrCreate(
            ['backing_id' => $backing2->id, 'type' => 'payment'],
            [
                'user_id' => $backer2->id,
                'campaign_id' => $campaignActive->id,
                'amount' => 1000000.00,
                'status' => 'success',
                'reference' => 'PAY-BACKER2-001',
            ]
        );

        // ==========================================
        // 2. CAMPAIGN REVIEW (Antrian Approval Admin)
        // ==========================================
        $campaignReview = Campaign::firstOrCreate(
            ['slug' => 'perpustakaan-digital-desa-sukamaju'],
            [
                'user_id' => $creator->id,
                'category_id' => $catEdu->id,
                'title' => 'Pembangunan Perpustakaan Digital Desa Sukamaju',
                'slug' => 'perpustakaan-digital-desa-sukamaju',
                'description' => 'Membangun sentra belajar komputer dan perpustakaan digital terpadu untuk 300 anak sekolah di pedalaman Desa Sukamaju agar mendapatkan akses internet edukatif.',
                'target_amount' => 25000000.00,
                'collected_amount' => 0.00,
                'deadline' => now()->addDays(40)->toDateString(),
                'status' => 'review',
            ]
        );
        CampaignImage::firstOrCreate(
            ['campaign_id' => $campaignReview->id, 'url' => 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=800'],
            ['is_primary' => true]
        );
        CampaignTier::firstOrCreate(
            ['campaign_id' => $campaignReview->id, 'name' => 'Sahabat Literasi'],
            [
                'min_amount' => 100000.00,
                'quota' => 100,
                'remaining_quota' => 100,
                'reward_description' => 'Kartu pos ucapan terima kasih dari adik-adik desa Sukamaju & pembatas buku',
            ]
        );

        // Campaign Review 2 (Untuk pengujian Admin Reject)
        $campaignReview2 = Campaign::firstOrCreate(
            ['slug' => 'pameran-seni-kriya-anyaman-bambu-modern'],
            [
                'user_id' => $creator->id,
                'category_id' => $catArt->id,
                'title' => 'Pameran Seni Kriya Anyaman Bambu Modern',
                'slug' => 'pameran-seni-kriya-anyaman-bambu-modern',
                'description' => 'Eksibisi karya seni kriya kontemporer berbahan bambu ramah lingkungan karya pemuda pengrajin lokal.',
                'target_amount' => 15000000.00,
                'collected_amount' => 0.00,
                'deadline' => now()->addDays(30)->toDateString(),
                'status' => 'review',
            ]
        );
        CampaignImage::firstOrCreate(
            ['campaign_id' => $campaignReview2->id, 'url' => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=800'],
            ['is_primary' => true]
        );

        // ==========================================
        // 3. CAMPAIGN DRAFT (Belum Diajukan)
        // ==========================================
        $campaignDraft = Campaign::firstOrCreate(
            ['slug' => 'film-animasi-pendek-legenda-danau-toba'],
            [
                'user_id' => $creator->id,
                'category_id' => $catArt->id,
                'title' => 'Film Animasi Pendek: Legenda Danau Toba',
                'slug' => 'film-animasi-pendek-legenda-danau-toba',
                'description' => 'Proyek pembuatan animasi 2D berstandar internasional yang mengangkat cerita rakyat nusantara dengan sentuhan visual modern.',
                'target_amount' => 50000000.00,
                'collected_amount' => 0.00,
                'deadline' => now()->addDays(60)->toDateString(),
                'status' => 'draft',
            ]
        );
        CampaignImage::firstOrCreate(
            ['campaign_id' => $campaignDraft->id, 'url' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=800'],
            ['is_primary' => true]
        );
        CampaignTier::firstOrCreate(
            ['campaign_id' => $campaignDraft->id, 'name' => 'Special Credits'],
            [
                'min_amount' => 50000.00,
                'quota' => 200,
                'remaining_quota' => 200,
                'reward_description' => 'Nama dicantumkan di credit title film dan link tonton premiere private',
            ]
        );

        // ==========================================
        // 4. CAMPAIGN SUCCESS (Selesai & Dana Cair)
        // ==========================================
        $campaignSuccess = Campaign::firstOrCreate(
            ['slug' => 'alat-deteksi-dini-banjir-berbasis-iot'],
            [
                'user_id' => $creator->id,
                'category_id' => $catEnv->id,
                'title' => 'Alat Deteksi Dini Banjir Berbasis IoT',
                'slug' => 'alat-deteksi-dini-banjir-berbasis-iot',
                'description' => 'Sistem sensor debit air nirkabel yang dipasang di bantaran sungai untuk memberi peringatan dini via WhatsApp dan sirine kepada warga.',
                'target_amount' => 5000000.00,
                'collected_amount' => 5500000.00,
                'deadline' => now()->subDays(2)->toDateString(),
                'status' => 'success',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subDays(35),
            ]
        );
        CampaignImage::firstOrCreate(
            ['campaign_id' => $campaignSuccess->id, 'url' => 'https://images.unsplash.com/photo-1517420704952-d9f39e95b43e?w=800'],
            ['is_primary' => true]
        );
        Transaction::firstOrCreate(
            ['campaign_id' => $campaignSuccess->id, 'type' => 'platform_fee'],
            [
                'user_id' => $creator->id,
                'amount' => 275000.00,
                'status' => 'success',
                'reference' => 'FEE-SUCCESS-001',
            ]
        );
        Transaction::firstOrCreate(
            ['campaign_id' => $campaignSuccess->id, 'type' => 'disbursement'],
            [
                'user_id' => $creator->id,
                'amount' => 5225000.00,
                'status' => 'success',
                'reference' => 'DISB-SUCCESS-001',
            ]
        );
    }
}
