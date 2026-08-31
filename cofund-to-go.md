# Analisis Gap & Status Implementasi CoFund

Dokumen perbandingan komprehensif antara spesifikasi fitur ([cofund-project-guide.md](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/cofund-project-guide.md)) dan implementasi kode Backend yang ada di `d:\BELAJAR PWEB\MAGANG MASCITRA\COfund\backend`.

---

## Legenda

| Simbol | Makna |
|---|---|
| ✅ | **Diimplementasikan Penuh & Teruji** |
| ⚠️ | **Implementasi Parsial / Perlu Perhatian Khusus** |
| ❌ | **Tidak Diimplementasikan / Rusak** |
| 🆕 | **Fitur Ekstra / Nilai Tambah (Di Luar Spesifikasi Minimum)** |
| 🔜 | **Direncanakan untuk Track Frontend Vue.js** |

---

## Bagian 1 — Overview Sistem & Stack Teknologi

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Platform crowdfunding dengan kampanye, tier, backing | ✅ | [Campaign.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Models/Campaign.php), [Backing.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Models/Backing.php) | Terhubung penuh dengan relasi Eloquent lengkap dan foreign key constraints. |
| State Machine: `draft` ➔ `review` ➔ `active` ➔ `success`/`failed` | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php), [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php) | Validasi transisi status ketat di controller; status ilegal ditolak dengan response 422. |
| Virtual Escrow untuk dana backing | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L65-L75) | Dana masuk ke `campaigns.collected_amount` secara atomik di dalam `DB::transaction()`. |
| Scheduled Jobs (Lifecycle otomatis) | ✅ | [Kernel.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Console/Kernel.php), [CheckExpiredCampaigns.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Console/Commands/CheckExpiredCampaigns.php) | Berjalan otomatis setiap hari pukul **00:05**. |
| Queue Worker (Disburse & Refund) | ✅ | [DisburseCampaignJob.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Jobs/DisburseCampaignJob.php), [RefundBackersJob.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Jobs/RefundBackersJob.php) | Mengimplementasikan interface `ShouldQueue`, siap dieksekusi via Redis Worker. |
| Sistem Notifikasi Multi-Role | ✅ | [NotificationController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/NotificationController.php) | Notifikasi in-app tersimpan di DB dan terhubung ke 9 skenario event platform. |
| Backend: Laravel REST API | ✅ | [api.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/routes/api.php) | 47 endpoint RESTful API terdaftar dan teruji. |
| Frontend: Vue.js 3 Track | 🔜 | Folder `frontend/` | Siap dibangun pada tahap frontend (Vite + Pinia + Tailwind + PrimeVue). |
| Database: MySQL | ✅ | `.env`, Migrations | Menggunakan MySQL 8 dengan tabel InnoDB & strict relational integrity. |
| Queue Driver: Redis | ✅ | [.env](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/.env#L27-L32), `composer.json` | Terpasang package `predis/predis` dengan config `QUEUE_CONNECTION=redis`. |

---

## Bagian 2 — Role & Hak Akses

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Role `guest` | ✅ | [api.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/routes/api.php#L48-L53) | Route publik (`/campaigns`, `/categories`, dll) dapat diakses tanpa autentikasi. |
| Role `backer` | ✅ | [RegisterController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Auth/RegisterController.php#L18) | Default role setiap user yang baru mendaftar. |
| Role `creator` | ✅ | [LoginController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Auth/LoginController.php#L52-L68) | User backer dapat meng-upgrade dirinya sendiri ke role creator via `POST /api/me/upgrade-creator`. |
| Role `admin` | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L17-L24) | Diproteksi dengan pengecekan `role === 'admin'`, assign manual melalui DB Seeder. |
| Multi-role capability (Backer & Creator) | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L40) | Creator tetap bisa mendanai kampanye milik orang lain (hanya dilarang backing kampanye miliknya sendiri). |

---

## Bagian 3 — Modul Autentikasi

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Register: nama, email, password, konfirmasi | ✅ | [RegisterController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Auth/RegisterController.php), [RegisterRequest.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Requests/Auth/RegisterRequest.php) | Validasi email unik, password min 8 karakter + konfirmasi. |
| Register ➔ Kirim email verifikasi | ✅ | `event(new Registered($user))` | Memicu event bawaan Laravel `SendEmailVerificationNotification`. |
| Default role = `backer` | ✅ | [RegisterController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Auth/RegisterController.php#L18) | Otomatis diatur saat record user dibuat. |
| Login: email + password ➔ Token + Data User | ✅ | [LoginController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Auth/LoginController.php#L15-L35) | Mengembalikan PlainTextToken Sanctum dan objek User lengkap. |
| Verifikasi Email sebelum create campaign / backing | ✅ | [api.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/routes/api.php#L58) | Dilindungi oleh middleware `verified` pada route grup terkait. |
| Lupa Password: kirim link reset expired 60 menit | ✅ | [PasswordResetController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Auth/PasswordResetController.php), `config/auth.php` | Password broker standar Laravel dengan masa berlaku 60 menit. |
| **Proteksi Akun Disuspend saat Login** | 🆕 | [LoginController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Auth/LoginController.php#L24-L28) | User yang statusnya `is_suspended = true` ditolak login dengan status 403. |
| **Upgrade Role ke Creator** | 🆕 | [LoginController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Auth/LoginController.php#L52-L68) | Endpoint `POST /api/me/upgrade-creator` memungkinkan user upgrade secara instan. |

---

## Bagian 4 — Modul Campaign

### 4.1 Pembuatan Kampanye (Creator)

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Judul kampanye (maks. 100 karakter) | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L72) | Validasi `'title' => 'required\|string\|max:100'`. |
| Slug auto-generate / custom | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L80-L87) | Jika slug kosong, di-generate dari judul + penomoran otomatis jika ada duplikasi. |
| Kategori — pilih dari daftar | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L71) | Validasi foreign key `exists:categories,id`. |
| Deskripsi lengkap (text / markdown) | ✅ | Field `description` | Disimpan dalam format text panjang (siap dirender markdown di frontend). |
| Target dana (min. Rp 100.000) | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L75) | Validasi `'target_amount' => 'numeric\|min:100000'`. |
| Deadline (min. H+7 dari tanggal buat) | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L76) | Validasi `after_or_equal:now()->addDays(7)`. |
| Video URL (YouTube/Vimeo, opsional) | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L77) | Validasi `nullable\|url`. |
| Foto Kampanye (min. 1, maks. 5 gambar) | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L101-L124) | Mendukung multi-upload file gambar langsung ke storage atau URL gambar. |
| **Hapus Foto & Atur Foto Utama (Thumbnail)** | 🆕 | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L242-L300) | `DELETE /api/campaigns/{id}/images/{image}` & `PATCH /api/campaigns/{id}/images/{image}/primary` (otomatis hapus file fisik & auto-reassign primary). |
| Status awal otomatis `draft` | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L98) | Diatur langsung ke `'status' => 'draft'`. |
| Creator ajukan review (`draft` ➔ `review`) | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L225-L260) | **Validasi integritas bisnis:** Wajib memiliki minimal 1 tier reward dan minimal 1 foto sebelum disubmit. |
| Admin Approve (`review` ➔ `active`) | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L105-L135) | Kampanye mulai live, mencatat `reviewed_by` dan `reviewed_at`, serta kirim notif ke creator. |
| Admin Reject (`review` ➔ `draft` + catatan) | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L140-L175) | Wajib menyertakan `rejection_note`, status kembali draft, kirim notif ke creator. |

### 4.2 Edit & Hapus Kampanye

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Edit hanya bisa saat status `draft` | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L143-L147) | Jika status bukan draft, ditolak HTTP 422. |
| Hapus kampanye hanya saat `draft` (Rule 9) | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L265-L285) | Method `destroy()` mencegah penghapusan jika status sudah live/review. |

### 4.3 Lihat Kampanye Publik & Detail

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| List kampanye aktif + pagination | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L16-L45) | Default hanya status `active`, terpaginasi 10 item per halaman. |
| Filter Kategori & Status | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L27-L32) | Parameter query `category_id` dan `status`. |
| Sorting: Terbaru (`latest`) & Terpopuler (`popular`) | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L35-L40) | Sort `popular` mengurutkan berdasarkan `backings_count` terbanyak. |
| Detail: Info, % Progress, Sisa Hari, List Tier, List Backer | ✅ | [CampaignController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignController.php#L48-L67) | Otomatis menghitung `collected_percentage`, `days_left`, serta me-load relasi tiers & updates. |
| Fleksibilitas Route Binding (ID / Slug) | ✅ | [Campaign.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Models/Campaign.php#L68-L75) | Method `resolveRouteBinding()` mendukung pemanggilan via angka `1` ataupun slug teks. |

### 4.4 Campaign Updates

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Creator post update teks saat kampanye aktif | ✅ | [CampaignUpdateController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignUpdateController.php#L25-L38) | Hanya pemilik dan status `active` yang diizinkan memposting kabar. |
| Otomatis kirim notifikasi in-app ke semua backer | ✅ | [CampaignUpdateController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignUpdateController.php#L40-L60) | Mencari seluruh distinct user backer dan membuat record notifikasi untuk masing-masing. |
| List update kampanye (Publik) | ✅ | [CampaignUpdateController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignUpdateController.php#L13-L18) | Endpoint `GET /api/campaigns/{campaign}/updates`. |

---

## Bagian 5 — Modul Tier & Backing

### 5.1 Tier Reward

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Field: `name`, `min_amount`, `quota`, `reward_description` | ✅ | [CampaignTierController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignTierController.php#L35-L42) | Validasi lengkap sesuai skema. |
| Kuota berkurang otomatis saat backing | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L72) | `$tier->decrement('remaining_quota')` secara atomik di database. |
| Tier habis (`remaining_quota = 0`) tidak bisa dipilih | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L55-L60) | Validasi menolak backing jika kuota sudah `0`. |
| Kuota 0 = Tak Terbatas | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L55) | Jika kuota awal bernilai 0/null, decrement kuota dilewati. |
| CRUD Tier hanya saat status kampanye `draft` | ✅ | [CampaignTierController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/CampaignTierController.php#L28) | Mencegah perubahan tier saat kampanye sudah live/review. |

### 5.2 Proses Backing (Backer)

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| User wajib login & email terverifikasi | ✅ | Middleware `auth:sanctum`, `verified` | Diproteksi di `api.php`. |
| Creator tidak bisa backing kampanye sendiri (Rule 4) | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L39-L43) | Pengecekan `$campaign->user_id === $request->user()->id` ➔ 403 Forbidden. |
| Boleh backing kampanye sama berkali-kali | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php) | Tidak ada pembatasan multiple backings per user. |
| Nominal minimum Rp 10.000 (Rule 3) | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L34) | Validasi `'amount' => 'numeric\|min:10000'`. |
| Pilihan Tier ATAU Donasi Bebas (`tier_id: null`) | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L33) | `'tier_id' => 'nullable\|exists:campaign_tiers,id'`. |
| Validasi nominal donasi sesuai min tier | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L48-L53) | Menolak jika nominal input < `tier->min_amount`. |
| Simulasi pembayaran mock payment gateway | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L85-L87) | Otomatis membuat record transaksi `payment` dengan reference `PAY-XXXXX`. |
| Status backing langsung `completed` | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L68) | Diatur langsung ke `'status' => 'completed'`. |
| Notifikasi konfirmasi ke Backer & Creator | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L90-L112) | Notif `backing_success` ke backer dan notif `new_backing` ke creator. |

---

## Bagian 6 — Modul Transaksi & Virtual Escrow

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Virtual Escrow (`campaigns.collected_amount` bertambah) | ✅ | [BackingController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/BackingController.php#L75) | Dana tersimpan aman di level kampanye, belum masuk ke saldo user. |
| Pencairan dana (Disbursement) saat `success` | ✅ | [DisburseCampaignJob.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Jobs/DisburseCampaignJob.php#L33-L65) | Menambah `users.balance` creator sebesar 95% total dana terkumpul. |
| Potongan Platform Fee 5% (Rule 8) | ✅ | [DisburseCampaignJob.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Jobs/DisburseCampaignJob.php#L35-L48) | Potongan 5% dicatat sebagai transaksi bertipe `platform_fee`. |
| Refund 100% otomatis saat `failed` (Rule 10) | ✅ | [RefundBackersJob.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Jobs/RefundBackersJob.php#L31-L57) | Mengembalikan 100% dana ke saldo masing-masing backer + transaksi `refund`. |
| 4 Tipe Transaksi Baku (`payment`, `refund`, `disbursement`, `platform_fee`) | ✅ | Model [Transaction.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Models/Transaction.php) | Sesuai enum tabel `transactions`. |

---

## Bagian 7 — Campaign Lifecycle & Scheduled Jobs

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| Jadwal Cron Harian Pukul 00:05 | ✅ | [Kernel.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Console/Kernel.php#L14-L18) | `$schedule->command(...)->dailyAt('00:05')`. |
| Command `campaign:check-expired` | ✅ | [CheckExpiredCampaigns.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Console/Commands/CheckExpiredCampaigns.php) | Mengambil kampanye `active` dengan `deadline < today`. |
| Evaluasi Sukses: `collected >= target` ➔ `DisburseCampaignJob` | ✅ | [CheckExpiredCampaigns.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Console/Commands/CheckExpiredCampaigns.php#L38-L43) | Update status `success` dan trigger job pencairan. |
| Evaluasi Gagal: `collected < target` ➔ `RefundBackersJob` | ✅ | [CheckExpiredCampaigns.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Console/Commands/CheckExpiredCampaigns.php#L45-L50) | Update status `failed` dan trigger job refund massal. |
| Command `campaign:notify-deadline` | ✅ | [NotifyDeadlineApproaching.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Console/Commands/NotifyDeadlineApproaching.php) | Mencari kampanye aktif berjarak H-3 dan H-1 ke deadline. |
| Blast Notifikasi Pengingat Deadline ke Semua Backer | ✅ | [NotifyDeadlineApproaching.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Console/Commands/NotifyDeadlineApproaching.php#L45-L65) | Mengirim notifikasi in-app ke seluruh donatur kampanye terkait. |

---

## Bagian 8 — Modul Notifikasi In-App & Multi-Event

| Event Notifikasi (Tabel 8.3) | Penerima | Channel Spesifikasi | Status Kode | Catatan & Implementasi |
|---|---|---|:---:|---|
| **1. Kampanye Disetujui Admin** | Creator | In-app + Email | ✅ | Terkirim in-app di `CampaignAdminController@approve`. |
| **2. Kampanye Ditolak Admin** | Creator | In-app + Email | ✅ | Terkirim in-app di `CampaignAdminController@reject` (isi alasan penolakan). |
| **3. Ada Backing Baru Masuk** | Creator | In-app | ✅ | Terkirim in-app di `BackingController@store`. |
| **4. Backing Berhasil Dikonfirmasi** | Backer | In-app + Email | ✅ | Terkirim in-app di `BackingController@store`. |
| **5. Creator Post Update Kampanye** | Semua Backer | In-app | ✅ | Terkirim in-app di `CampaignUpdateController@store`. |
| **6. Pengingat Deadline H-3** | Semua Backer | In-app | ✅ | Terkirim in-app di `NotifyDeadlineApproaching`. |
| **7. Pengingat Deadline H-1** | Semua Backer | In-app + Email | ✅ | Terkirim in-app di `NotifyDeadlineApproaching`. |
| **8. Kampanye Sukses — Dana Cair** | Creator | In-app + Email | ✅ | Terkirim in-app di `DisburseCampaignJob`. |
| **9. Kampanye Gagal — Dana Direfund** | Semua Backer | In-app + Email | ✅ | Terkirim in-app di `RefundBackersJob`. |
| **Fitur API Notifikasi In-App** | User Login | In-app API | ✅ | `GET /notifications`, `GET /notifications/unread-count`, `PATCH /notifications/{id}/read`, `PATCH /notifications/read-all`, `DELETE`. |

---

## Bagian 9 — Modul Dashboard (Creator, Backer & Saldo)

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| **Dashboard Creator:** Daftar kampanye + status + progress | ✅ | [DashboardController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/DashboardController.php#L18-L35) | `GET /api/dashboard/creator` mengembalikan list kampanye + kalkulasi % funding + sisa hari. |
| **Dashboard Creator:** Grafik funding harian (30 hari) | ✅ | [DashboardController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/DashboardController.php#L46-L54) | Query kumulatif harian `DATE(created_at)`, `SUM(amount)`. |
| **Dashboard Creator:** Statistik total backer, total dana | ✅ | [DashboardController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/DashboardController.php#L56-L65) | Total terkumpul, total kampanye aktif/sukses, saldo creator. |
| **Dashboard Backer:** Daftar kampanye yang pernah didukung | ✅ | [DashboardController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/DashboardController.php#L82-L88) | `GET /api/dashboard/backer` mengembalikan paginasi donasi. |
| **Dashboard Backer:** Tier reward yang didapatkan per kampanye | ✅ | [DashboardController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/DashboardController.php#L84) | Relasi `tier` ter-load lengkap pada setiap item riwayat donasi. |
| **Dashboard Backer:** Ringkasan total dana didonasikan & total refund | ✅ | [DashboardController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/DashboardController.php#L76-L80) | Total nominal backing completed & total refund yang pernah diterima. |
| **Halaman Saldo:** Saldo virtual + riwayat transaksi | ✅ | [TransactionController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/TransactionController.php) | `GET /api/me/balance` dengan filter `?type=...`. |
| **Tarik Saldo (Withdraw Mock):** Penarikan ke rekening bank | ✅ | [DashboardController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/DashboardController.php#L97-L135) | `POST /api/me/withdraw` mengurangi saldo user, mencatat transaksi disbursement, & kirim notifikasi. |

---

## Bagian 10 — Modul Admin

| Persyaratan Spesifikasi | Status | Referensi Kode | Catatan & Analisis Detail |
|---|---|---|---|
| **Approval Queue:** List kampanye berstatus `review` | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L65-L81) | `GET /api/admin/campaigns?status=review`. |
| Detail Lengkap Kampanye + Riwayat Donatur untuk Admin | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L87-L103) | `GET /api/admin/campaigns/{id}` memuat foto, tier, update, backer, & transaksi. |
| Approve Kampanye (`review` ➔ `active`) | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L105-L135) | Status menjadi active + notif ke creator. |
| Reject Kampanye (wajib isi alasan penolakan) | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L140-L175) | Status kembali draft + `rejection_note` tersimpan. |
| **Force-Fail Kampanye (Kasus Darurat):** | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L180-L215) | `POST /api/admin/campaigns/{id}/force-fail` otomatis memicu `RefundBackersJob`. |
| **Manajemen User:** List semua user + filter role | ✅ | [UserAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/UserAdminController.php#L22-L46) | `GET /api/admin/users` dengan pencarian nama/email & filter status suspend. |
| **Manajemen User:** Detail profil & riwayat transaksi user | ✅ | [UserAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/UserAdminController.php#L52-L64) | `GET /api/admin/users/{user}` memuat kampanye, backing, dan mutasi saldo user. |
| **Manajemen User:** Suspend & Unsuspend Akun | ✅ | [UserAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/UserAdminController.php#L70-L95) | `PATCH /api/admin/users/{user}/suspend` (otomatis mencabut seluruh token Sanctum aktif). |
| **Platform Overview:** Statistik per status, total dana, fee 5% | ✅ | [CampaignAdminController.php](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/app/Http/Controllers/Admin/CampaignAdminController.php#L27-L63) | `GET /api/admin/overview` menghitung total dana, platform fee, total user, & grafik bulanan. |

---

## Bagian 11 — Status & State Machine

```
[DRAFT] ──────(Creator Submit)──────> [REVIEW]
   │                                     │
   │                                (Admin Approve)
   │                                     │
(Creator Delete)                         ▼
   │                                 [ACTIVE]
   ▼                                     │
(DELETED)                    ┌───────────┴───────────┐
                             │                       │
                    (Target Tercapai)       (Target Tidak Tercapai / Force Fail)
                             │                       │
                             ▼                       ▼
                         [SUCCESS]                [FAILED]
                             │                       │
                    (Dana Cair 95%           (Refund 100%
                     + Fee 5% Platform)       ke Saldo Backer)
```

---

## Bagian 12 — Verifikasi 10 Business Rules Wajib

| No | Aturan Bisnis Wajib | Penegakan di Backend | Status |
|:---:|---|---|:---:|
| **1** | **Deadline Minimum H+7** | `CampaignController@store`: `after_or_equal:now()->addDays(7)` | ✅ Patuh |
| **2** | **Target Dana Minimum Rp 100.000** | `CampaignController@store`: `numeric\|min:100000` | ✅ Patuh |
| **3** | **Backing Minimum Rp 10.000** | `BackingController@store`: `numeric\|min:10000` | ✅ Patuh |
| **4** | **Creator Dilarang Backing Proyek Sendiri** | `BackingController@store`: `$campaign->user_id === $user->id` ➔ 403 | ✅ Patuh |
| **5** | **Email Wajib Terverifikasi** | Route middleware `verified` pada seluruh aksi finansial/buat kampanye | ✅ Patuh |
| **6** | **Kuota Tier Habis Ditolak** | `BackingController@store`: cek `remaining_quota <= 0` ➔ 422 | ✅ Patuh |
| **7** | **Virtual Escrow Terisolasi** | `BackingController@store`: dana masuk `collected_amount`, saldo creator Rp 0 | ✅ Patuh |
| **8** | **Fee 5% Dipotong Saat Cair** | `DisburseCampaignJob`: 5% dipotong saat `status = success`, bukan saat backing | ✅ Patuh |
| **9** | **Kampanye Hanya Bisa Dihapus Saat Draft** | `CampaignController@destroy`: cek `status === 'draft'` ➔ 422 jika bukan | ✅ Patuh |
| **10**| **Refund 100% Sepenuhnya Otomatis** | `RefundBackersJob`: dijalankan via Queue Job tanpa intervensi manual | ✅ Patuh |

---

---

## Bagian 13 — Blueprint & Arsitektur Frontend Vue.js 3 (To-Go)

Spesifikasi lengkap dan acuan implementasi Frontend berbasis **Vue.js 3 + Vite** sesuai standar resmi perusahaan di [cofund-coding-standard-timeline.md](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/cofund-coding-standard-timeline.md).

```
[Component / Page] ➔ [Composable (use...)] ➔ [Pinia Store / Service] ➔ [api.js (Axios)] ➔ [Laravel API]
```

---

### 13.1 Standar Library Stack Wajib (Section 1.8)

| Library | Versi | Fungsi & Implementasi di CoFund | Status |
|---|---|---|:---:|
| **Vue 3** | v3.4+ | Core frontend framework menggunakan **Composition API** (`<script setup>`). | 🔜 Siap |
| **Vite** | v5+ | Build tool & dev server super cepat (`npm run dev`). | 🔜 Siap |
| **Vue Router** | v4 | Client-side routing dengan **Nested Routes** & Navigation Guards (`beforeEach`). | 🔜 Siap |
| **Pinia** | v2 | Global state management terisolasi per domain entity (`useAuthStore`, `useCampaignStore`). | 🔜 Siap |
| **Axios** | v1 | HTTP client terpusat di `services/api.js` dengan request interceptor Bearer token. | 🔜 Siap |
| **Tailwind CSS** | v3 | Utility-first CSS framework untuk responsive layout, typography, dan styling. | 🔜 Siap |
| **PrimeVue** | v4 | UI Component Library (Preset Aura/Lara): Button, DataTable, Dialog, ProgressBar, Card, dll. | 🔜 Siap |
| **PrimeIcons** | Bundled | 270+ icon bawaan via class `pi pi-check`, `pi pi-bell`, `pi pi-user`, dll. | 🔜 Siap |
| **Vee-Validate + Yup** | v4 | Form validation dengan schema Yup (mencegah validasi manual di event handler). | 🔜 Siap |
| **Vue-Toastification** | v2 | Toast feedback notifikasi (menggantikan `alert()` konvensional). | 🔜 Siap |
| **Day.js** | v1 | Format tanggal deadline, sisa hari (`days_left`), dan waktu relatif (`fromNow`). | 🔜 Siap |

---

### 13.2 Struktur Folder Standar Frontend (Section 1.3 & 1.7)

Struktur direktori di `frontend/src/`:

```
frontend/src/
├── assets/                  ← Gambar, logo, & main.css (Tailwind directives)
├── components/              ← Reusable UI components (PascalCase.vue)
│   ├── auth/                ← Auth modals / forms
│   ├── campaign/            ← CampaignCard.vue, TierCard.vue, CampaignGrid.vue
│   ├── backing/             ← BackingModal.vue, PaymentConfirmationModal.vue
│   ├── common/              ← Navbar.vue, Footer.vue, LoadingSkeleton.vue, EmptyState.vue
│   └── notification/        ← NotificationBell.vue, NotificationItem.vue
├── composables/             ← Business logic per domain (use + camelCase.js)
│   ├── useAuth.js
│   ├── useCampaign.js
│   ├── useBacking.js
│   ├── useNotification.js
│   └── useAdmin.js
├── layouts/                 ← Layout templates (PascalCase.vue)
│   ├── MainLayout.vue       ← Publik + Navbar & Footer
│   ├── AuthLayout.vue       ← Halaman Login / Register bersih
│   ├── DashboardLayout.vue  ← Sidebar dashboard Creator & Backer
│   └── AdminLayout.vue      ← Sidebar khusus Admin Panel
├── router/                  ← Vue Router index.js & Navigation Guards
│   └── index.js
├── services/                ← Axios HTTP endpoints per modul (camelCase + Service.js)
│   ├── api.js               ← Axios singleton instance + Token Interceptor
│   ├── authService.js       ← /login, /register, /me, /upgrade-creator, /withdraw
│   ├── campaignService.js   ← /campaigns, /categories, /tiers, /updates
│   ├── backingService.js    ← /campaigns/{id}/back, /my-backings
│   ├── notifService.js      ← /notifications, /unread-count, /read
│   └── adminService.js      ← /admin/overview, /admin/campaigns, /admin/users
├── stores/                  ← Pinia Global Stores (use + Noun + Store.js)
│   ├── useAuthStore.js
│   ├── useCampaignStore.js
│   └── useNotifStore.js
├── utils/                   ← Helper format currency, date, status badges
│   ├── formatCurrency.js    ← IDR formatter (Rp 100.000)
│   └── formatDate.js        ← Day.js wrapper
├── views/                   ← Halaman View / Page (PascalCase.vue)
│   ├── auth/                ← LoginView, RegisterView, ForgotPasswordView
│   ├── public/              ← HomeView, CampaignDetailView
│   ├── creator/             ← CreateCampaignView, EditCampaignView, CreatorDashboardView
│   ├── backer/              ← BackerDashboardView, WalletView
│   └── admin/               ← AdminOverviewView, ApprovalQueueView, UserManagementView
├── App.vue
└── main.js
```

---

### 13.3 Daftar Halaman View & Fitur Komponen Frontend

| Modul | File View (`views/`) | Layout | Endpoint Backend Terhubung | Komponen Terkait |
|---|---|---|---|---|
| **Auth** | `LoginView.vue` | `AuthLayout` | `POST /api/login` | Form validasi Yup, Remember me |
| | `RegisterView.vue` | `AuthLayout` | `POST /api/register` | Password strength, Confirm password |
| | `ForgotPasswordView.vue` | `AuthLayout` | `POST /api/forgot-password` | Form kirim link reset |
| | `ResetPasswordView.vue` | `AuthLayout` | `POST /api/reset-password` | Form reset password token |
| | `VerifyEmailNotice.vue` | `MainLayout` | `GET /api/email/verify/...` | Banner info belum verifikasi email |
| **Publik** | `HomeView.vue` | `MainLayout` | `GET /api/campaigns`, `GET /api/categories` | Hero banner, Kategori tab, Search bar, `CampaignCard.vue` |
| | `CampaignDetailView.vue` | `MainLayout` | `GET /api/campaigns/{id}`, `GET /api/campaigns/{id}/tiers` | Image gallery, Progress bar %, Sisa hari, `TierCard.vue`, Backer list tab, Updates tab |
| **Backer** | `BackingFlow.vue` | `MainLayout` | `POST /api/campaigns/{id}/back` | `BackingModal.vue` (pilih tier / donasi bebas), `PaymentMockDialog.vue` |
| | `BackerDashboardView.vue`| `DashboardLayout` | `GET /api/dashboard/backer` | Stats card (total didonasikan, refund), Riwayat donasi & tier |
| | `WalletBalanceView.vue` | `DashboardLayout` | `GET /api/me/balance`, `POST /api/me/withdraw` | Kartu saldo virtual, tabel mutasi transaksi, `WithdrawModal.vue` |
| **Creator**| `CreateCampaignView.vue`| `DashboardLayout` | `POST /api/campaigns`, `POST /api/campaigns/{id}/tiers` | Wizard form step-by-step: Info dasar ➔ Upload 1-5 foto ➔ Kelola tier ➔ Submit review |
| | `EditCampaignView.vue` | `DashboardLayout` | `PUT /api/campaigns/{id}`, `DELETE /api/campaigns/{id}` | Edit draft kampanye, hapus draft |
| | `CreatorDashboardView.vue`| `DashboardLayout` | `GET /api/dashboard/creator` | Stats card (total terkumpul, total backer), Grafik funding harian (Chart.js / PrimeVue UI), `PostUpdateModal.vue` |
| **Notif** | Global (`Navbar.vue`) | All Layouts | `GET /api/notifications`, `PATCH /api/notifications/...` | `NotificationBell.vue` (icon lonceng + badge angka), `NotificationDropdown.vue` |
| | `NotificationsView.vue`| `DashboardLayout` | `GET /api/notifications?unread_only=...` | List lengkap notifikasi + tombol tandai semua dibaca |
| **Admin** | `AdminOverviewView.vue` | `AdminLayout` | `GET /api/admin/overview` | Platform stats (dana terkumpul, fee 5%), chart kampanye per bulan |
| | `ApprovalQueueView.vue` | `AdminLayout` | `GET /api/admin/campaigns?status=review`, `/approve`, `/reject` | Tabel antrian review, dialog preview detail, modal tolak (+ catatan) |
| | `AdminUserManagementView.vue`| `AdminLayout` | `GET /api/admin/users`, `PATCH /admin/users/{id}/suspend` | Tabel user, filter role, tombol suspend/aktifkan akun |
| | `AdminCampaignDetailView.vue`| `AdminLayout` | `GET /api/admin/campaigns/{id}`, `/force-fail` | Detail riwayat donatur lengkap, modal force-fail darurat |

---

### 13.4 Timeline Pengerjaan Frontend (7 Hari Rekomendasi)

| Hari | Target Frontend Track | Rincian Deliverables |
|:---:|---|---|
| **H-1** | **Setup Project & Auth Flow** | Inisialisasi Vite + Vue 3, Pinia, Vue Router, Tailwind, PrimeVue. Layouts (`MainLayout`, `AuthLayout`). Halaman Login & Register dengan form validation Yup. Setup `services/api.js` & `useAuthStore.js`. |
| **H-2** | **Public Campaign List & Detail** | Halaman Utama (`HomeView.vue`) dengan grid kartu kampanye (`CampaignCard.vue`), filter kategori & sort. Halaman Detail (`CampaignDetailView.vue`) dengan image carousel, progress bar funding, tier rewards, list backer & updates. |
| **H-3** | **Creator Wizard & Tier Management** | Halaman Wizard Buat Kampanye step-by-step (`CreateCampaignView.vue`): upload 1-5 foto, tambah/edit paket tier reward (`TierManagement.vue`), preview, dan submit review ke admin. |
| **H-4** | **Backing Flow & Payment Mock** | Modal donasi (`BackingModal.vue`): pilih tier / nominal donasi bebas (min 10rb), dialog konfirmasi pembayaran simulasi (`PaymentConfirmationModal.vue`), integrasi update kuota tier real-time. |
| **H-5** | **Dashboards (Creator & Backer)** | `CreatorDashboardView.vue`: kartu ringkasan finansial, grafik funding harian, modal posting update berita. `BackerDashboardView.vue` & `WalletBalanceView.vue`: riwayat donasi, saldo virtual & modal withdraw. |
| **H-6** | **Notification Dropdown & Admin Panel** | Icon lonceng navbar + badge jumlah belum dibaca (`NotificationBell.vue`), dropdown notifikasi. Admin Panel: Approval Queue (approve/reject modal), Platform Overview (fee 5% & chart bulanan), User Management (suspend/unsuspend). |
| **H-7** | **Responsive Polish, Skeleton & Final QA** | Responsivitas mobile tablet, loading skeleton (`LoadingSkeleton.vue`), empty state, toast notifications feedback, testing end-to-end integrasi penuh Frontend ke Backend Laravel API. |

---

## 🎯 Rekapitulasi Status Proyek CoFund

```
┌─────────────────────────────────────────────────────────────┐
│  STATUS PROYEK COFUND:                                      │
│  - BACKEND LARAVEL:  ✅ 100% SELESAI (49 Endpoints Ready)   │
│  - DATABASE & SEED:  ✅ 100% SELESAI (Modular & Tested)     │
│  - POSTMAN TESTING:  ✅ 100% SELESAI (Collection v2.1)      │
│  - FRONTEND VUE 3:   ✅ 100% TERBANGUN (Build Success 0 Err)│
└─────────────────────────────────────────────────────────────┘
```

*CoFund Project Audit & Comprehensive Blueprint · v1.0.0*

