# CoFund — Panduan Proyek untuk AI

**Platform Crowdfunding Lokal**
Versi 1.0.0 | Stack: Laravel + Vue.js | Proyek Pelatihan Magang

> Dokumen ini adalah panduan teknis untuk AI/asisten coding yang mengerjakan proyek CoFund. Ikuti spesifikasi ini secara ketat — semua business rules di Bagian 12 bersifat wajib dan tidak boleh dilanggar.

---

## 1. Overview Sistem

CoFund adalah platform crowdfunding lokal sederhana namun dengan kompleksitas bisnis realistis:
- Creator membuat kampanye dengan target dana dan deadline.
- Backer mendanai kampanye dengan memilih tier atau nominal bebas.
- Dana ditahan di **virtual escrow** hingga kampanye **berhasil** (target tercapai sebelum deadline) atau **gagal** (memicu refund otomatis via queue job).

Kompleksitas yang harus diimplementasikan: state machine, virtual escrow, scheduled jobs, queue worker, dan sistem notifikasi multi-role.

### Stack Teknologi
- **Backend**: Laravel (REST API + Scheduled Commands + Queue Worker)
- **Frontend**: Vue.js
- **Database**: MySQL / PostgreSQL
- **Queue Driver**: Redis / Database Queue

---

## 2. Role & Akses

| Role | Deskripsi | Default | Akses Utama |
|---|---|---|---|
| `guest` | Belum login | Otomatis | Lihat kampanye publik |
| `backer` | User yang mendanai | Setelah register | Backing, riwayat, dashboard backer |
| `creator` | Pembuat kampanye | Request upgrade | CRUD kampanye, post update, analytics |
| `admin` | Pengelola platform | Assign manual | Approve kampanye, monitor transaksi |

- Satu user **bisa** memiliki role `backer` dan `creator` sekaligus.
- Role `admin` hanya bisa di-assign manual (database/panel admin) — tidak ada self-registration sebagai admin.

---

## 3. Modul Autentikasi

### 3.1 Register
- Input: nama, email, password, konfirmasi password
- Validasi: email unik, password minimum 8 karakter
- Setelah register: kirim email verifikasi
- Default role: `backer`

### 3.2 Login
- Input: email dan password
- Response: token (Sanctum/Passport) + data user
- Redirect ke halaman sesuai role setelah login berhasil

### 3.3 Verifikasi Email
- Link verifikasi dikirim otomatis saat register
- Akun belum terverifikasi **tidak bisa** membuat kampanye atau melakukan backing

### 3.4 Lupa Password
- User input email → sistem kirim link reset
- Link reset **expired setelah 60 menit**

---

## 4. Modul Campaign

### 4.1 Buat Kampanye (Creator)

Field input:
- Judul kampanye (maks. 100 karakter)
- Slug — auto-generate dari judul, bisa di-edit manual
- Kategori — pilih dari daftar tersedia
- Deskripsi lengkap (rich text/markdown)
- Target dana (min. Rp 100.000)
- Deadline (min. H+7 dari tanggal submit)
- Video embed URL (YouTube/Vimeo) — opsional
- Foto kampanye (min. 1, maks. 5 gambar)

Alur:
1. Creator isi form lengkap → status `draft`
2. Creator submit untuk review → status `review`
3. Admin approve → status `active` (live)
4. Admin reject → status kembali `draft` + catatan penolakan

### 4.2 Edit Kampanye
- Hanya bisa diedit saat status `draft`
- Setelah `active`, creator hanya bisa menambahkan campaign update

### 4.3 Lihat Kampanye (Publik)
- List kampanye aktif dengan filter: kategori, status, terbaru, terpopuler
- Halaman detail: info kampanye, progress bar + persentase + sisa hari, daftar tier, daftar backer, update dari creator

### 4.4 Campaign Update (Creator)
- Creator bisa post update teks selama kampanye aktif
- Semua backer kampanye mendapat notifikasi saat ada update baru

---

## 5. Modul Tier & Backing

### 5.1 Tier Reward

Wajib minimal 1 tier per kampanye.

| Field | Deskripsi | Contoh |
|---|---|---|
| Nama tier | Label publik | "Early Bird" |
| Min. nominal | Batas bawah donasi untuk tier ini | Rp 50.000 |
| Kuota | Jumlah slot (0 = tak terbatas) | 100 |
| Deskripsi reward | Apa yang didapat backer | Akses beta + stiker |

- Kuota berkurang otomatis setiap ada backing baru.
- Tier dengan `remaining_quota = 0` tidak bisa dipilih backer baru.

### 5.2 Proses Backing (Backer)

Alur:
1. Backer pilih kampanye aktif
2. Pilih tier atau nominal bebas (tanpa reward)
3. Konfirmasi & review ringkasan
4. Simulasi pembayaran via mock payment gateway
5. Sukses → backing status `completed`, dana masuk escrow
6. Notifikasi konfirmasi ke backer (in-app + email)

Aturan:
- User wajib login & email terverifikasi
- Creator **tidak bisa** backing kampanye miliknya sendiri
- Satu user boleh backing kampanye yang sama lebih dari sekali
- Nominal minimum Rp 10.000

---

## 6. Modul Transaksi & Escrow

### 6.1 Virtual Escrow
- Backing sukses → dana masuk escrow (transaksi tipe `payment`)
- `campaigns.collected_amount` bertambah otomatis
- Dana **tidak pernah** langsung masuk saldo creator selama kampanye berlangsung

### 6.2 Pencairan (Disbursement)
Dipicu otomatis saat status `success`:
- Platform fee dipotong (5% dari total collected amount)
- Sisa dana dicairkan ke `users.balance` creator
- Transaksi `disbursement` dan `platform_fee` dibuat otomatis

### 6.3 Refund Otomatis
Dipicu otomatis saat status `failed`:
- Semua backing `completed` di-refund
- Dana kembali ke `users.balance` masing-masing backer
- Transaksi `refund` dibuat per backer
- Status backing → `refunded`

### 6.4 Tipe Transaksi

| Tipe | Keterangan |
|---|---|
| `payment` | Backer backing — dana masuk virtual escrow |
| `refund` | Pengembalian dana saat kampanye gagal |
| `disbursement` | Pencairan dana ke creator saat sukses |
| `platform_fee` | Potongan fee 5% saat disbursement |

---

## 7. Campaign Lifecycle (Scheduled Jobs)

Semua proses lifecycle dijalankan otomatis via Laravel Scheduler setiap hari pukul **00:05**.

### 7.1 `CheckExpiredCampaigns` Command
```
php artisan campaign:check-expired
```
Logika:
1. Ambil kampanye `active` dengan `deadline < hari ini`
2. Jika `collected_amount >= target_amount` → status `success`, dispatch `DisburseCampaignJob`
3. Jika `collected_amount < target_amount` → status `failed`, dispatch `RefundBackersJob`

### 7.2 `DisburseCampaignJob` (Queue Job)
- Hitung platform fee (5% dari collected amount)
- Buat transaksi `platform_fee`
- Tambah saldo creator: collected_amount − platform fee
- Buat transaksi `disbursement`
- Kirim notifikasi ke creator: kampanye berhasil, dana dicairkan

### 7.3 `RefundBackersJob` (Queue Job)
- Ambil semua backing `completed` dari kampanye ini
- Untuk tiap backing: tambah saldo backer sebesar nominal backing
- Buat transaksi `refund` per backer
- Update status backing → `refunded`
- Kirim notifikasi ke backer: kampanye gagal, dana dikembalikan

### 7.4 `NotifyDeadlineApproaching` Command
```
php artisan campaign:notify-deadline
```
- Dijalankan setiap hari
- Cari kampanye aktif dengan deadline H-3 dan H-1
- Kirim notifikasi ke semua backer kampanye tersebut

---

## 8. Modul Notifikasi

### 8.1 In-App Notification
- Disimpan di tabel `notifications`
- Bell icon di navbar + badge jumlah belum dibaca
- Klik notifikasi → tandai dibaca + redirect ke halaman terkait

### 8.2 Email Notification
- Dikirim via Laravel Mail + Queue (non-blocking)
- Template email tersedia untuk tiap jenis event

### 8.3 Event Notifikasi

| Event | Penerima | Channel |
|---|---|---|
| Kampanye disetujui admin | Creator | In-app + Email |
| Kampanye ditolak admin | Creator | In-app + Email |
| Ada backing baru masuk | Creator | In-app |
| Backing berhasil dikonfirmasi | Backer | In-app + Email |
| Creator post update kampanye | Semua backer | In-app |
| Deadline H-3 | Semua backer | In-app |
| Deadline H-1 | Semua backer | In-app + Email |
| Kampanye sukses — dana cair | Creator | In-app + Email |
| Kampanye gagal — dana direfund | Semua backer | In-app + Email |

---

## 9. Modul Dashboard

### 9.1 Dashboard Creator
- Daftar kampanye miliknya + status & progress funding
- Grafik funding harian (kumulatif backing per hari)
- Statistik: total backer, total terkumpul, persentase target
- Tombol post update untuk kampanye aktif

### 9.2 Dashboard Backer
- Daftar kampanye yang pernah didanai + statusnya
- Reward tier yang didapat per kampanye
- Ringkasan: total dana pernah dibacking, total refund diterima

### 9.3 Halaman Saldo (Backer & Creator)
- Saldo virtual user saat ini
- Riwayat transaksi dengan filter per tipe & tanggal
- Tombol withdraw (opsional — implementasi mock)

---

## 10. Modul Admin

### 10.1 Approval Queue
- List kampanye status `review` yang menunggu diproses
- Admin lihat detail lengkap sebelum approve/reject
- Reject wajib mengisi catatan alasan penolakan
- Creator dapat notifikasi setelah diproses

### 10.2 Manajemen Kampanye
- List semua kampanye + filter status
- Lihat detail & seluruh riwayat backing
- Force-fail kampanye untuk kasus khusus

### 10.3 Manajemen User
- List semua user + role masing-masing
- Suspend/aktifkan kembali akun user
- Lihat riwayat transaksi per user

### 10.4 Overview Platform
- Total kampanye per status
- Total dana terkumpul seluruh platform
- Total platform fee diterima
- Grafik kampanye baru per bulan

---

## 11. Status & State Machine

### Campaign Status

| Status | Deskripsi |
|---|---|
| `draft` | Baru dibuat atau ditolak — bisa diedit creator |
| `review` | Menunggu persetujuan admin |
| `active` | Live, bisa menerima backing |
| `success` | Target tercapai — dana dicairkan ke creator |
| `failed` | Deadline lewat, target tidak tercapai — refund otomatis |

**Alur utama:**
```
draft → (submit) → review → (approve) → active → (deadline & sukses) → success
```

**Jalur alternatif:**
```
review → (reject) → draft            # bisa diedit & disubmit ulang
active → (deadline & gagal) → failed  # refund otomatis ke semua backer
```

### Backing Status

| Status | Deskripsi |
|---|---|
| `pending` | Backing dibuat, menunggu konfirmasi pembayaran |
| `completed` | Pembayaran sukses, dana masuk escrow |
| `refunded` | Dana dikembalikan karena kampanye gagal |

```
pending → (payment success) → completed → (campaign failed) → refunded
```

---

## 12. Business Rules (WAJIB — tidak boleh dilanggar)

1. **Deadline minimum**: kampanye harus punya deadline minimal 7 hari dari tanggal submit.
2. **Target minimum**: target dana minimal Rp 100.000 per kampanye.
3. **Backing minimum**: nominal backing minimal Rp 10.000 per transaksi.
4. Creator **tidak bisa** backing kampanye miliknya sendiri.
5. **Email terverifikasi**: backing dan pembuatan kampanye hanya untuk akun terverifikasi.
6. **Kuota tier**: saat `remaining_quota = 0`, tier tidak bisa dipilih backer baru.
7. **Escrow**: dana tidak pernah langsung masuk creator — selalu lewat proses lifecycle otomatis.
8. **Platform fee**: 5% dipotong saat disbursement, **bukan** saat backing masuk.
9. Kampanye **tidak bisa dihapus** setelah status bukan `draft`.
10. **Refund sepenuhnya otomatis** — tidak ada intervensi manual dalam proses pengembalian dana.

---

## Catatan untuk AI/Developer

- Selalu validasi business rules di atas pada level backend (bukan hanya frontend), karena ini adalah aturan integritas data finansial.
- State transition kampanye dan backing harus diimplementasikan sebagai state machine eksplisit (jangan hanya update kolom status secara bebas) agar transisi ilegal tidak mungkin terjadi.
- Semua operasi yang menyentuh saldo (`disbursement`, `refund`, `platform_fee`) harus atomik (gunakan DB transaction) untuk menghindari race condition, terutama karena dijalankan lewat queue job.
- Struktur folder Laravel disarankan mengikuti konvensi: Commands untuk scheduled command (§7.1, §7.4), Jobs untuk queue job (§7.2, §7.3), Notifications untuk sistem notifikasi (§8), dan Policies untuk otorisasi per role (§2).
- Dokumen ini adalah panduan fitur untuk keperluan pelatihan magang — Project CoFund v1.0.0.
