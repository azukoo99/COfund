# CoFund RESTful API — Dokumentasi Arsitektur & Spesifikasi Master

Selamat datang di Dokumentasi Resmi Master API **CoFund** (*Crowdfunding Platform Indonesia*). Dokumen ini menyajikan panduan arsitektural menyeluruh, spesifikasi teknis, standar protokol, sistem keamanan, antrian latar belakang, hingga tata kelola pengujian API untuk seluruh ekosistem backend CoFund.

---

## 1. Pendahuluan

Platform CoFund adalah sistem penggalangan dana terdesentralisasi untuk proyek inovasi teknologi, sosial, dan karya kreatif lokal dengan mekanisme penjaminan keamanan dana berbasis **Virtual Escrow**.

### Informasi Dasar API
- **Base URL Pengembangan (Local):** `http://localhost:8000/api`
- **Format Data:** JSON (`Content-Type: application/json` & `Accept: application/json`)
- **Protokol:** HTTP/1.1 & HTTP/2 over TLS/HTTPS
- **Standar Format Waktu:** ISO 8601 UTC (`YYYY-MM-DDTHH:mm:ss.sssZ`)
- **Mata Uang Default:** Rupiah Indonesia (IDR / `DECIMAL(15,2)`)

### Standar HTTP Headers
Setiap request ke CoFund API wajib menyertakan header berikut:
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <sanctum_token>   # (Wajib untuk endpoint terproteksi)
```

---

## 2. Ikhtisar Arsitektur Sistem

CoFund dibangun menggunakan framework **Laravel 10** dengan arsitektur berlapis (*Layered Architecture*) yang memisahkan tanggung jawab secara tegas (*Separation of Concerns*).

### Technology Stack
- **Framework:** Laravel 10.x (PHP 8.2+)
- **Database:** MySQL 8.0+ / MariaDB 10.4+
- **Autentikasi:** Laravel Sanctum (Stateful Token-Based Auth)
- **Queue & Async Worker:** Sync (Local Dev) / Redis & Laravel Horizon (Production)
- **Frontend SPA Client:** Vue 3 (Vite, Pinia, Vue Router, TailwindCSS)

### Diagram Arsitektur Keseluruhan (ASCII Diagram)

```text
                                 [ CLIENT APPLICATIONS ]
                     (Vue 3 SPA Web, Mobile App, Postman Client)
                                         │
                                         ▼ [HTTP / RESTful JSON]
                              [ Nginx / Apache Gateway ]
                                         │
                   ┌─────────────────────┴─────────────────────┐
                   │               LARAVEL BACKEND             │
                   │                                           │
                   │  [ HTTP Middlewares ]                     │
                   │  ├── ForceJsonResponse                   │
                   │  ├── ThrottleRequests (Rate Limiting)     │
                   │  ├── Authenticate (Sanctum Bearer)        │
                   │  └── EnsureEmailIsVerified ('verified')   │
                   │                                           │
                   │  [ Controllers Layer ]                    │
                   │  ├── Auth & Password Controllers          │
                   │  ├── Campaign & Tier Controllers          │
                   │  ├── Backing & Escrow Controllers         │
                   │  ├── Wallet & Dashboard Controllers       │
                   │  └── Admin Controllers                    │
                   │                                           │
                   │  [ Core Domain & Transaction Layer ]      │
                   │  ├── Form Requests & Validation           │
                   │  ├── DB::transaction() Safety             │
                   │  └── Models & Eloquent ORM                │
                   │                                           │
                   │  [ Event & Background Worker ]            │
                   │  ├── In-App Notification Dispatcher       │
                   │  ├── DisburseCampaignJob (Success 95%)    │
                   │  └── RefundBackersJob (Failed 100%)       │
                   └─────────────────────┬─────────────────────┘
                                         │
                   ┌─────────────────────┴─────────────────────┐
                   │            DATA PERSISTENCE               │
                   │  ├── MySQL DB (Users, Campaigns, etc.)    │
                   │  └── Storage Disk (Images & Media Assets) │
                   └───────────────────────────────────────────┘
```

---

## 3. Memulai (Getting Started)

### Prasyarat Sistem
1. PHP >= 8.2 (dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`)
2. Composer >= 2.x
3. MySQL Server >= 8.0
4. Node.js >= 18.x & NPM (untuk frontend & testing runner)

### Langkah Instalasi Backend
```bash
# 1. Masuk ke direktori backend
cd backend

# 2. Salin environment file
cp .env.example .env

# 3. Instal dependensi Composer
composer install

# 4. Generate Application Key
php artisan key:generate

# 5. Konfigurasi Database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=cofund_db
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Jalankan Migrasi & Database Seeder
php artisan migrate:fresh --seed

# 7. Buat Symbolic Link Storage Foto
php artisan storage:link

# 8. Jalankan Server Pengembangan
php artisan serve --port=8000
```

---

## 4. Otentikasi & Verifikasi Email

Sistem autentikasi CoFund mengimplementasikan **Laravel Sanctum**. Setiap permintaan login yang berhasil menerbitkan token dengan format `id|token_hash`.

### Siklus Token & Akses:
1. **Registrasi (`POST /api/register`):** Akun baru otomatis memiliki role `backer`. Sistem mengirimkan email verifikasi.
2. **Email Verification (`GET /api/email/verify/{id}/{hash}`):** Menggunakan signed URL. Pengguna yang belum terverifikasi dicegah membuat kampanye atau melakukan backing (`403 Your email address is not verified`).
3. **Penyimpanan Token:** Disimpan pada tabel `personal_access_tokens`.
4. **Pencabutan Token (`POST /api/logout`):** Menghapus token yang sedang aktif pada sesi tersebut.

---

## 5. Peran Pengguna & Matriks Akses Global (Global RBAC)

Platform mengelola 3 tingkat hak akses (*Roles*):
- **`backer` (Donatur):** Dapat mendanai kampanye aktif, melihat mutasi saldo/refund, menarik saldo (*withdraw*), dan meng-upgrade akun menjadi creator.
- **`creator` (Penggagas Proyek):** Memiliki semua hak backer ditambah membuat draf kampanye, mengunggah foto, mengelola reward tier, memposting kabar terbaru, dan mengajukan review ke admin.
- **`admin` (Administrator Platform):** Memiliki hak penuh meninjau kampanye, menyetujui (*approve*), menolak (*reject*), membatalkan paksa (*force fail*), dan menangguhkan (*suspend*) akun pengguna.

---

## 6. Pembatasan Laju (Rate Limiting)

Untuk menjaga stabilitas dari serangan *DDoS* dan *Brute-Force*, sistem menerapkan limitasi request berbasis IP & User ID:
- **Global API Limiter:** `60 request / menit` per IP.
- **Login Limiter:** `10 percobaan / menit`.
- **Register Limiter:** `6 percobaan / menit`.
- **Forgot Password Limiter:** `5 request / menit`.

Jika melampaui batas, sistem mengembalikan HTTP `429 Too Many Requests`:
```json
{
  "message": "Too Many Attempts.",
  "retry_after": 60
}
```

---

## 7. Penanganan Error Standar (Error Handling)

Semua response error diformat dalam struktur JSON konsisten:

### 1. HTTP 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 2. HTTP 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 3. HTTP 404 Not Found
```json
{
  "message": "Resource tidak ditemukan."
}
```

### 4. HTTP 422 Unprocessable Content (Validation Error)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "target_amount": [
      "Target dana minimal adalah Rp 100.000."
    ]
  }
}
```

### 5. HTTP 500 Internal Server Error
```json
{
  "message": "Terjadi kesalahan pada server internal."
}
```

---

## 8. Ringkasan Seluruh Endpoint API (Master Endpoint Registry)

| Modul | Method | URL Path | Middleware | Deskripsi Singkat |
| :--- | :---: | :--- | :--- | :--- |
| **Auth** | `POST` | `/api/register` | `guest` | Registrasi akun baru |
| **Auth** | `GET` | `/api/email/verify/{id}/{hash}` | `signed` | Konfirmasi tautan verifikasi email |
| **Auth** | `POST` | `/api/login` | `guest` | Autentikasi & penerbitan token |
| **Auth** | `POST` | `/api/logout` | `auth:sanctum` | Pencabutan token aktif |
| **Auth** | `GET` | `/api/me` | `auth:sanctum` | Profil akun yang sedang login |
| **Auth** | `POST` | `/api/me/upgrade-creator` | `auth:sanctum, verified` | Peningkatan role menjadi Creator |
| **Auth** | `POST` | `/api/forgot-password` | `guest` | Pengiriman email link reset sandi |
| **Auth** | `POST` | `/api/reset-password` | `guest` | Atur ulang kata sandi baru |
| **Campaigns** | `GET` | `/api/categories` | Publik | Daftar kategori proyek |
| **Campaigns** | `GET` | `/api/campaigns` | Publik | Explore, filter, & search kampanye |
| **Campaigns** | `GET` | `/api/campaigns/{slug}` | Publik | Detail lengkap kampanye & tier |
| **Campaigns** | `POST` | `/api/campaigns` | `auth:sanctum, verified` | Buat draf kampanye baru |
| **Campaigns** | `PUT` | `/api/campaigns/{id}` | `auth:sanctum, verified` | Update data draf kampanye |
| **Campaigns** | `DELETE`| `/api/campaigns/{id}` | `auth:sanctum, verified` | Hapus draf kampanye |
| **Campaigns** | `POST` | `/api/campaigns/{id}/submit-review` | `auth:sanctum, verified` | Ajukan draf ke admin review |
| **Tiers** | `GET` | `/api/campaigns/{id}/tiers` | Publik | Daftar paket reward tier kampanye |
| **Tiers** | `POST` | `/api/campaigns/{id}/tiers` | `auth:sanctum, verified` | Tambah reward tier baru (draf) |
| **Tiers** | `PUT` | `/api/campaigns/{id}/tiers/{tier}`| `auth:sanctum, verified` | Update nominal/kuota tier |
| **Tiers** | `DELETE`| `/api/campaigns/{id}/tiers/{tier}`| `auth:sanctum, verified` | Hapus tier reward |
| **Images** | `POST` | `/api/campaigns/{id}/images` | `auth:sanctum, verified` | Upload foto/URL galeri (maks 5) |
| **Images** | `PATCH`| `/api/campaigns/{id}/images/{img}/primary` | `auth:sanctum, verified` | Set gambar sebagai cover utama |
| **Images** | `DELETE`| `/api/campaigns/{id}/images/{img}`| `auth:sanctum, verified` | Hapus foto dari galeri |
| **Updates** | `GET` | `/api/campaigns/{id}/updates` | Publik | Riwayat kabar terbaru kampanye |
| **Updates** | `POST` | `/api/campaigns/{id}/updates` | `auth:sanctum, verified` | Posting update & broadcast donatur |
| **Backing** | `POST` | `/api/campaigns/{id}/back` | `auth:sanctum, verified` | Donasi bebas / klaim tier reward |
| **Backing** | `GET` | `/api/my-backings` | `auth:sanctum, verified` | Riwayat proyek yang didanai user |
| **Wallet** | `GET` | `/api/me/balance` | `auth:sanctum` | Saldo virtual & riwayat mutasi |
| **Wallet** | `POST` | `/api/me/withdraw` | `auth:sanctum` | Tarik saldo ke rekening bank |
| **Notifikasi**| `GET` | `/api/notifications` | `auth:sanctum` | Daftar notifikasi in-app |
| **Notifikasi**| `GET` | `/api/notifications/unread-count` | `auth:sanctum` | Jumlah notifikasi belum dibaca |
| **Notifikasi**| `PATCH`| `/api/notifications/{id}/read` | `auth:sanctum` | Tandai 1 notifikasi terbaca |
| **Notifikasi**| `PATCH`| `/api/notifications/read-all` | `auth:sanctum` | Tandai seluruh notifikasi terbaca |
| **Notifikasi**| `DELETE`| `/api/notifications/{id}` | `auth:sanctum` | Hapus notifikasi |
| **Dashboard** | `GET` | `/api/dashboard/creator` | `auth:sanctum` | Analitik finansial & performa creator |
| **Dashboard** | `GET` | `/api/dashboard/backer` | `auth:sanctum` | Ringkasan portofolio donatur |
| **Admin** | `GET` | `/api/admin/overview` | `admin` | Statistik platform & ringkasan fee |
| **Admin** | `GET` | `/api/admin/campaigns` | `admin` | Antrian tinjauan & semua kampanye |
| **Admin** | `GET` | `/api/admin/campaigns/{id}` | `admin` | Detail audit kampanye |
| **Admin** | `POST` | `/api/admin/campaigns/{id}/approve`| `admin` | Setujui kampanye menjadi aktif |
| **Admin** | `POST` | `/api/admin/campaigns/{id}/reject` | `admin` | Tolak kampanye dengan catatan |
| **Admin** | `POST` | `/api/admin/campaigns/{id}/force-fail`| `admin` | Batalkan paksa & auto-refund |
| **Admin** | `GET` | `/api/admin/users` | `admin` | Daftar seluruh akun pengguna |
| **Admin** | `GET` | `/api/admin/users/{id}` | `admin` | Detail profil & histori user |
| **Admin** | `PATCH`| `/api/admin/users/{id}/suspend` | `admin` | Tangguhkan / buka blokir akun |

---

## 9. Sistem Event & Listener

Platform mengintegrasikan notifikasi berbasis event (*Event-Driven Notification Architecture*):
- **`campaign_approved`:** Diterbitkan saat admin menyetujui kampanye review. Penerima: Creator.
- **`campaign_rejected`:** Diterbitkan saat admin menolak kampanye draf. Penerima: Creator.
- **`donation_received`:** Diterbitkan saat donasi baru masuk. Penerima: Creator.
- **`backing_success`:** Diterbitkan saat pembayaran backing berhasil. Penerima: Backer.
- **`campaign_update`:** Diterbitkan saat kabar baru diposting. Penerima: Seluruh Donatur Aktif.
- **`refund_processed`:** Diterbitkan saat kampanye gagal/force-fail. Penerima: Seluruh Donatur.

---

## 10. Pekerjaan Latar Belakang (Queue & Schedule)

### 1. `DisburseCampaignJob`
- **Fungsi:** Dijalankan saat kampanye aktif mencapai target dana (>= 100%) dan deadline berakhir.
- **Mekanisme:**
  1. Menghitung fee platform (5%) dan mentransfer ke akun platform.
  2. Mengkredit 95% total dana terkumpul ke `wallet_balance` creator.
  3. Mencatat transaksi bertipe `disbursement` dan `platform_fee`.
  4. Mengubah status kampanye menjadi `success`.

### 2. `RefundBackersJob`
- **Fungsi:** Dijalankan saat kampanye gagal mencapai target (< 100%) atau di-*Force Fail* oleh admin.
- **Mekanisme:**
  1. Melakukan perulangan seluruh record backing kampanye tersebut.
  2. Mengembalikan 100% nominal donasi ke `wallet_balance` masing-masing donatur.
  3. Mengubah status backing menjadi `refunded`.
  4. Mencatat transaksi bertipe `refund`.
  5. Mengubah status kampanye menjadi `failed`.

---

## 11. Daftar Navigasi Modul API

Untuk panduan teknis mendalam per modul, silakan merujuk ke dokumen berikut:
- 📖 [Modul 1: Autentikasi & Akun](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/auth.md)
- 📖 [Modul 2: Manajemen Kampanye](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/campaigns.md)
- 📖 [Modul 3: Manajemen Paket Reward (Tier)](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/tier.md)
- 📖 [Modul 4: Galeri Foto & Cover Kampanye](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/campaign-image.md)
- 📖 [Modul 5: Kabar Terbaru & Update Proyek](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/campaign-update.md)
- 📖 [Modul 6: Pendanaan & Backing Donasi](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/backing.md)
- 📖 [Modul 7: Dompet Virtual & Withdraw Saldo](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/wallet.md)
- 📖 [Modul 8: Notifikasi & Transaksi Platform](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/transaction.md)
- 📖 [Modul 9: Dashboard Creator & Backer](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/creator.md)
- 📖 [Modul 10: Administrasi & Kontrol Platform](file:///d:/BELAJAR%20PWEB/MAGANG%20MASCITRA/COfund/backend/docs/api/admin.md)

---

## 12. Masalah yang Diketahui (Known Issues & Architectural Notes)

1. **Sinkronisasi Queue Connection di Lingkungan Lokal:**
   - Di lingkungan lokal Windows tanpa Redis service, selalu pastikan `.env` menyetel `QUEUE_CONNECTION=sync` agar Job refund dan disbursement dieksekusi secara instan tanpa menunggu worker terpisah.
2. **Kesesuaian Enum Tipe Transaksi Database:**
   - Kolom `type` pada tabel `transactions` mencakup: `payment`, `disbursement`, `refund`, `platform_fee`, dan `withdraw`.

---

## 13. Pengaturan Postman & Export Collection

CoFund menyediakan master Postman Collection komprehensif berisi 60 skenario pengujian endpoint API:
- File Collection: `CoFund_Postman_Collection.json` (terletak di root proyek).
- Variabel Environment Utama yang digunakan:
  - `{{base_url}}`: `http://localhost:8000/api`
  - `{{auth_token}}`: Bearer token sesi aktif.
  - `{{admin_token}}`: Bearer token khusus role admin.
  - `{{campaign_id}}`, `{{tier_id}}`, `{{image_id}}`, dll.

Untuk menjalankan otomatisasi pengujian Postman melalui terminal / CLI:
```bash
newman run CoFund_Postman_Collection.json -e cofund_environment.json
```

---

## 14. Perintah Pengembangan & Debugging

Berikut adalah kumpulan perintah Artisan esensial untuk pengembangan dan pengujian harian:

```bash
# Refresh Database & Seed Data Baru
php artisan migrate:fresh --seed

# Pembersihan Seluruh Cache Aplikasi
php artisan optimize:clear

# Cek Rute API yang Terdaftar
php artisan route:list --path=api

# Jalankan Background Worker Antrian (Jika menggunakan Redis/Database Queue)
php artisan queue:work

# Buka Konsol Interaktif Laravel
php artisan tinker
```
