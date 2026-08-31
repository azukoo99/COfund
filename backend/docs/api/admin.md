# Modul 10: Administrasi & Kontrol Platform (`admin.md`)

Dokumentasi komprehensif untuk subsistem Antrian Tinjauan Kampanye (*Review Queue*), Persetujuan & Penolakan (*Approval/Rejection*), Pembatalan Paksa (*Force Fail*), Manajemen Pengguna (*User Management* & *Suspend*), serta Analitik Finansial Platform pada CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Administrasi & Kontrol Platform** memberikan kewenangan penuh kepada Administrator untuk menjaga kepatuhan dan integritas ekosistem CoFund. Administrator dapat meninjau kampanye yang diajukan oleh creator, menyetujui kampanye agar aktif dan mulai menerima donasi, menolak kampanye yang tidak sesuai panduan (dengan memberikan feedback alasan penolakan), membatalkan paksa kampanye yang bermasalah (*Force Fail* yang memicu pengembalian dana donatur), serta menonaktifkan (*suspend*) akun pengguna yang melanggar aturan.

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\Admin\CampaignAdminController`<br>`App\Http\Controllers\Admin\UserAdminController` | Menangani operasi tinjauan kampanye, agregasi statistik finansial platform, dan manajemen akun pengguna. |
| **Models** | `App\Models\Campaign`<br>`App\Models\User`<br>`App\Models\Transaction`<br>`App\Models\Notification` | Menjalankan perubahan status status kampanye, mutasi fee platform, notifikasi admin, dan flag `is_suspended`. |
| **Queue Jobs** | `App\Jobs\RefundBackersJob` | Dijalankan saat kampanye di-*Force Fail* untuk mengembalikan 100% dana donatur ke saldo virtual dompet mereka. |
| **Middleware** | `auth:sanctum`<br>`verified` (Otorisasi: `role === 'admin'`) | Memastikan hanya pengguna dengan role `admin` yang dapat mengakses seluruh endpoint `/api/admin/*`. |

### Alur Kerja (Workflow)

```text
[ADMIN REVIEW & ACTION FLOW]

Creator Submits Campaign (Status: REVIEW)
   │
   ├───> GET /api/admin/campaigns (Filter: status=review)
   │        │
   │        ├── Admin membaca deskripsi, target dana, deadline, dan foto/tier
   │        │
   │        ├── PILIHAN A: POST /api/admin/campaigns/{id}/approve
   │        │     ├── Update status kampanye -> 'active'
   │        │     ├── Kirim notifikasi 'campaign_approved' ke Creator
   │        │     └── Return HTTP 200 (Kampanye mulai tayang publik)
   │        │
   │        ├── PILIHAN B: POST /api/admin/campaigns/{id}/reject (rejection_note)
   │        │     ├── Update status kampanye -> 'draft'
   │        │     ├── Kirim notifikasi 'campaign_rejected' (+ catatan penolakan) ke Creator
   │        │     └── Return HTTP 200 (Creator dapat mengedit dan mengajukan ulang)
   │        │
   │        └── PILIHAN C: POST /api/admin/campaigns/{id}/force-fail (Pelanggaran saat aktif)
   │              ├── Update status kampanye -> 'failed'
   │              ├── Dispatch RefundBackersJob -> Kembalikan 100% dana seluruh donatur
   │              ├── Kirim notifikasi ke Creator & seluruh donatur
   │              └── Return HTTP 200 (Refund Sukses)
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Admin/
│   │           ├── CampaignAdminController.php
│   │           └── UserAdminController.php
│   └── Models/
│       ├── Campaign.php
│       ├── Notification.php
│       ├── Transaction.php
│       └── User.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Ringkasan Finansial Platform (Admin Overview)
- **HTTP Method:** `GET`
- **Path:** `/api/admin/overview`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified` (Role: `admin`)
- **Deskripsi:** Mengambil metrik total dana terkumpul platform, total fee platform 5% dari kampanye sukses, total pengguna terdaftar, dan distribusi status kampanye.

#### Response Sukses (HTTP 200 OK)
```json
{
  "total_collected_platform": "150000000.00",
  "total_platform_fee_collected": "7500000.00",
  "total_campaigns": 45,
  "total_users": 120,
  "campaigns_by_status": {
    "draft": 8,
    "review": 4,
    "active": 18,
    "success": 12,
    "failed": 3
  },
  "monthly_campaigns": [
    {
      "month": "2026-07",
      "total": 15
    },
    {
      "month": "2026-08",
      "total": 30
    }
  ]
}
```

---

### 4.2. Antrian Tinjauan & Seluruh Kampanye (Admin Campaign Queue)
- **HTTP Method:** `GET`
- **Path:** `/api/admin/campaigns`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified` (Role: `admin`)
- **Deskripsi:** Menampilkan seluruh kampanye di platform dengan dukungan filter status (`review`, `active`, `draft`, `success`, `failed`), pencarian judul/creator, dan paginasi.

#### Tabel Parameter Query
| Parameter | Posisi | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `status` | Query | `string` | Opsional | `in:draft,review,active,success,failed` | Filter spesifik status kampanye |
| `search` | Query | `string` | Opsional | `string` | Cari berdasarkan judul kampanye atau nama creator |
| `page` | Query | `integer` | Opsional | `min:1` | Nomor halaman paginasi (default: `1`) |

#### Response Sukses (HTTP 200 OK)
```json
{
  "data": [
    {
      "id": 12,
      "title": "Robot Pembersih Sampah Sungai Otomatis",
      "slug": "robot-pembersih-sampah-sungai-otomatis",
      "status": "review",
      "target_amount": "25000000.00",
      "collected_amount": "0.00",
      "deadline": "2026-11-30",
      "creator": {
        "id": 3,
        "name": "Tech Labs",
        "email": "creator@example.com"
      },
      "category": {
        "id": 1,
        "name": "Teknologi & Inovasi"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 4
  }
}
```

---

### 4.3. Persetujuan Kampanye (Approve Campaign)
- **HTTP Method:** `POST`
- **Path:** `/api/admin/campaigns/{campaign_id}/approve`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified` (Role: `admin`)
- **Deskripsi:** Menyetujui draf kampanye yang berstatus `review` menjadi `active`.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Kampanye berhasil disetujui dan kini berstatus aktif.",
  "campaign": {
    "id": 12,
    "status": "active"
  }
}
```

---

### 4.4. Penolakan Kampanye (Reject Campaign)
- **HTTP Method:** `POST`
- **Path:** `/api/admin/campaigns/{campaign_id}/reject`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified` (Role: `admin`)
- **Deskripsi:** Menolak kampanye `review` dan mengembalikannya ke status `draft` dengan catatan perbaikan untuk creator.

#### Tabel Parameter Body (JSON)
| Parameter | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `rejection_note` | `string` | Wajib | `required\|string\|min:5` | Alasan penolakan dan instruksi perbaikan |

#### Request Payload
```json
{
  "rejection_note": "Target dana terlalu besar tanpa rincian anggaran yang jelas. Mohon lengkapi RAB pada deskripsi."
}
```

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Kampanye telah ditolak dan dikembalikan ke status draf dengan catatan revisi.",
  "campaign": {
    "id": 12,
    "status": "draft"
  }
}
```

---

### 4.5. Pembatalan Paksa Kampanye (Force Fail & Auto Refund)
- **HTTP Method:** `POST`
- **Path:** `/api/admin/campaigns/{campaign_id}/force-fail`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified` (Role: `admin`)
- **Deskripsi:** Membatalkan secara paksa kampanye yang sedang `active` akibat pelanggaran regulasi/penipuan dan secara otomatis mengeksekusi pengembalian 100% dana donatur.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Kampanye berhasil digagalkan secara paksa. Seluruh dana donatur telah dikembalikan 100% ke saldo virtual dompet mereka."
}
```

---

### 4.6. Daftar Pengguna Platform (Admin User Management)
- **HTTP Method:** `GET`
- **Path:** `/api/admin/users`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified` (Role: `admin`)
- **Deskripsi:** Menampilkan seluruh daftar pengguna terdaftar beserta informasi peran (`role`), status suspend (`is_suspended`), dan saldo dompet.

#### Response Sukses (HTTP 200 OK)
```json
{
  "users": {
    "data": [
      {
        "id": 3,
        "name": "Tech Labs",
        "email": "creator@example.com",
        "role": "creator",
        "is_suspended": false,
        "wallet_balance": "47500000.00",
        "campaigns_count": 2,
        "backings_count": 0
      }
    ],
    "current_page": 1,
    "total": 1
  }
}
```

---

### 4.7. Tangguhkan / Buka Penangguhan Akun (Toggle Suspend User)
- **HTTP Method:** `PATCH`
- **Path:** `/api/admin/users/{user_id}/suspend`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified` (Role: `admin`)
- **Deskripsi:** Mengubah flag status `is_suspended` pengguna (jika aktif menjadi suspend, jika suspend menjadi aktif kembali).

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Status penangguhan akun berhasil diperbarui.",
  "user": {
    "id": 3,
    "name": "Tech Labs",
    "is_suspended": true
  }
}
```

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `AdminOverview`
```json
{
  "total_collected_platform": "150000000.00",
  "total_platform_fee_collected": "7500000.00",
  "total_campaigns": 45,
  "total_users": 120,
  "campaigns_by_status": {
    "draft": 8,
    "review": 4,
    "active": 18,
    "success": 12,
    "failed": 3
  }
}
```

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: POST /api/admin/campaigns/{id}/approve
pm.test("Status code is 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Campaign status updated to ACTIVE", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.campaign.status).to.eql("active");
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-ADM-01** | Akses admin overview dengan token admin | `GET /api/admin/overview` | `200 OK`, metrik platform lengkap. |
| **TC-ADM-02** | Akses admin endpoint dengan token backer biasa | Token `role === 'backer'` | `403 Forbidden`, Unauthorized access. |
| **TC-ADM-03** | Setujui kampanye review | `POST /api/admin/campaigns/{review_id}/approve` | `200 OK`, status kampanye menjadi `active`. |
| **TC-ADM-04** | Tolak kampanye review dengan catatan | `{"rejection_note": "Harap perbaiki RAB"}` | `200 OK`, status kembali ke `draft`. |
| **TC-ADM-05** | Force fail kampanye aktif bermasalah | `POST /api/admin/campaigns/{active_id}/force-fail` | `200 OK`, status `failed` & refund dieksekusi. |
| **TC-ADM-06** | Suspend akun pengguna | `PATCH /api/admin/users/{user_id}/suspend` | `200 OK`, `is_suspended` menjadi `true`. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Error: `403 Forbidden - Unauthorized Admin Access`**
   - *Penyebab:* Pengguna yang login memiliki kolom `role` bernilai `backer` atau `creator`.
   - *Solusi:* Pastikan login menggunakan akun administrator (`role = 'admin'`).

2. **Eksekusi Refund Gagal saat Force Fail di Lingkungan Lokal**
   - *Penyebab:* `QUEUE_CONNECTION=redis` tidak berjalan di background worker.
   - *Solusi:* Pastikan `.env` menggunakan `QUEUE_CONNECTION=sync` untuk eksekusi synchronous instan di development lokal.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `GET /api/admin/overview` | ❌ | ❌ | ❌ | ✅ |
| `GET /api/admin/campaigns` | ❌ | ❌ | ❌ | ✅ |
| `GET /api/admin/campaigns/{id}` | ❌ | ❌ | ❌ | ✅ |
| `POST /api/admin/campaigns/{id}/approve` | ❌ | ❌ | ❌ | ✅ |
| `POST /api/admin/campaigns/{id}/reject` | ❌ | ❌ | ❌ | ✅ |
| `POST /api/admin/campaigns/{id}/force-fail` | ❌ | ❌ | ❌ | ✅ |
| `GET /api/admin/users` | ❌ | ❌ | ❌ | ✅ |
| `GET /api/admin/users/{id}` | ❌ | ❌ | ❌ | ✅ |
| `PATCH /api/admin/users/{id}/suspend` | ❌ | ❌ | ❌ | ✅ |
