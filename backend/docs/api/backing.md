# Modul 6: Pendanaan & Backing Kampanye (`backing.md`)

Dokumentasi komprehensif untuk subsistem Transaksi Backing (*Pledge/Donation*), Validasi Kuota Reward Tier, Penampungan Dana di Virtual Escrow, dan Riwayat Donasi Pengguna pada platform CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Pendanaan & Backing** menangani proses transaksi kontribusi dana dari donatur (*backer*) kepada kampanye yang sedang aktif. Mendukung dua mode donasi: **Donasi Bebas** (tanpa reward) dan **Klaim Paket Reward Tier** (dengan validasi kuota otomatis). Seluruh dana donasi yang masuk dicatat dengan status `completed` dan diakumulasikan ke saldo penampungan virtual (*Virtual Escrow*) sampai kampanye mencapai batas akhir deadline.

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\BackingController` | Memvalidasi integritas donasi, memeriksa batas minimal tier & kuota, mencatat transaksi, memotong kuota tier, mengupdate `collected_amount`, dan memicu notifikasi ke creator & backer. |
| **Models** | `App\Models\Backing`<br>`App\Models\Campaign`<br>`App\Models\CampaignTier`<br>`App\Models\Transaction`<br>`App\Models\Notification` | Mengelola data backing, relasi foreign key, mutasi riwayat transaksi, dan notifikasi konfirmasi. |
| **Middleware** | `auth:sanctum`<br>`verified` | Memastikan hanya pengguna terautentikasi dan email terverifikasi yang dapat mendanai proyek. |

### Alur Kerja (Workflow)

```text
[BACKING / DONATION WORKFLOW]

Backer                                 BackingController                         Database (DB::transaction)
   │                                           │                                              │
   ├───> POST /campaigns/{id}/back             │                                              │
   │     (amount, tier_id [optional]) ────────>│                                              │
   │                                           ├── 1. Validasi: Campaign status == 'active'   │
   │                                           ├── 2. Validasi: amount >= min_amount tier     │
   │                                           ├── 3. Validasi: tier remaining_quota > 0      │
   │                                           │                                              │
   │                                           ├── [BEGIN TRANSACTION] ──────────────────────>│
   │                                           ├── Insert record 'backings' (completed) ─────>│
   │                                           ├── Insert record 'transactions' (payment) ───>│
   │                                           ├── Decrement tier remaining_quota (if tier) ─>│
   │                                           ├── Increment campaign collected_amount ──────>│
   │                                           ├── [COMMIT TRANSACTION] ─────────────────────>│
   │                                           │                                              │
   │                                           ├── Kirim Notifikasi ke Backer & Creator ─────>│
   │                                           └── Return HTTP 201 (Backing Data + Reference) │
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── BackingController.php
│   └── Models/
│       ├── Backing.php
│       ├── Campaign.php
│       ├── CampaignTier.php
│       ├── Notification.php
│       └── Transaction.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Lakukan Donasi / Backing Proyek
- **HTTP Method:** `POST`
- **Path:** `/api/campaigns/{campaign_id}/back`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Mendanai kampanye aktif secara bebas atau dengan memilih reward tier tertentu.

#### Tabel Parameter Body (JSON)
| Parameter | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `amount` | `numeric` | Wajib | `required\|numeric\|min:10000` | Nominal donasi minimal Rp 10.000 (atau minimal nominal tier jika `tier_id` diisi) |
| `tier_id` | `integer` | Opsional | `nullable\|exists:campaign_tiers,id` | ID reward tier yang dipilih (opsional jika donasi bebas) |

#### Request Payload (Dengan Pilihan Tier)
```json
{
  "amount": 150000,
  "tier_id": 1
}
```

#### Response Sukses (HTTP 201 Created)
```json
{
  "message": "Backing berhasil diproses. Terima kasih atas dukungan Anda!",
  "backing": {
    "id": 24,
    "user_id": 2,
    "campaign_id": 1,
    "campaign_tier_id": 1,
    "amount": "150000.00",
    "status": "completed",
    "created_at": "2026-08-28T07:15:00.000000Z",
    "tier": {
      "id": 1,
      "name": "Early Supporter",
      "reward_description": "Akses eksklusif discord & sertifikat digital"
    }
  }
}
```

#### Efek Samping (Side Effects)
1. Record baru tersimpan di tabel `backings` dengan status `completed`.
2. Record mutasi tersimpan di tabel `transactions` bertipe `payment`.
3. Kolom `collected_amount` pada tabel `campaigns` bertambah sebesar nilai `amount`.
4. Kolom `remaining_quota` pada `campaign_tiers` berkurang 1 (jika tier memiliki kuota terbatas).
5. Notifikasi `donation_received` terkirim ke Creator dan notifikasi `backing_success` terkirim ke Backer.

#### Tabel Error Handling
| HTTP Code | Body JSON | Kondisi |
| :--- | :--- | :--- |
| `422 Unprocessable Content` | `{"message":"Kampanye ini tidak sedang aktif untuk menerima donasi."}` | Kampanye berstatus `draft`, `review`, `success`, atau `failed`. |
| `422 Unprocessable Content` | `{"message":"Nominal donasi kurang dari batas minimal tier yang dipilih (Rp 50.000)."}` | Nominal `amount` lebih kecil daripada `min_amount` tier. |
| `422 Unprocessable Content` | `{"message":"Kuota paket reward tier ini telah habis (Sold Out)."}` | `remaining_quota` tier bernilai `0`. |

---

### 4.2. Riwayat Backing Saya (Donatur)
- **HTTP Method:** `GET`
- **Path:** `/api/my-backings`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Mengambil riwayat proyek yang pernah didanai oleh pengguna yang sedang login beserta status backing (`completed` atau `refunded`).

#### Response Sukses (HTTP 200 OK)
```json
{
  "backings": {
    "data": [
      {
        "id": 24,
        "amount": "150000.00",
        "status": "completed",
        "created_at": "2026-08-28T07:15:00.000000Z",
        "campaign": {
          "id": 1,
          "title": "Smart Trash Bin Otomatis IoT",
          "slug": "smart-trash-bin-otomatis-iot"
        },
        "tier": {
          "id": 1,
          "name": "Early Supporter"
        }
      }
    ],
    "current_page": 1,
    "total": 1
  }
}
```

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `Backing`
```json
{
  "id": 24,
  "user_id": 2,
  "campaign_id": 1,
  "campaign_tier_id": 1,
  "amount": "150000.00",
  "status": "completed",
  "created_at": "2026-08-28T07:15:00.000000Z",
  "updated_at": "2026-08-28T07:15:00.000000Z"
}
```

### Tabel Definisi Kolom Data
| Kolom | Tipe Database | Nullable | Deskripsi |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary key unik backing |
| `user_id` | `BIGINT UNSIGNED` | Tidak | Foreign key donatur (`users.id`) |
| `campaign_id` | `BIGINT UNSIGNED` | Tidak | Foreign key kampanye (`campaigns.id`) |
| `campaign_tier_id`| `BIGINT UNSIGNED` | Ya | Foreign key paket reward (`campaign_tiers.id`), bernilai `NULL` untuk donasi bebas |
| `amount` | `DECIMAL(15,2)` | Tidak | Nominal dana yang didonasikan |
| `status` | `ENUM('pending','completed','refunded')` | Tidak | Status transaksi backing (default: `completed`) |
| `created_at` | `TIMESTAMP` | Ya | Waktu pelaksanaan transaksi backing |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: POST /api/campaigns/{id}/back
pm.test("Status code is 201 Created", function () {
    pm.response.to.have.status(201);
});

pm.test("Backing status is completed", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.backing.status).to.eql("completed");
    pm.expect(parseFloat(jsonData.backing.amount)).to.be.above(0);
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-BACK-01** | Backing donasi bebas tanpa tier | `{"amount": 50000}` | `201 Created`, tier_id null, collected_amount bertambah. |
| **TC-BACK-02** | Backing dengan klaim tier valid | `{"amount": 100000, "tier_id": 1}` | `201 Created`, remaining_quota tier berkurang 1. |
| **TC-BACK-03** | Backing gagal nominal di bawah minimum tier | `{"amount": 20000, "tier_id": 1}` (min: 50.000) | `422 Unprocessable Content`, nominal kurang dari min tier. |
| **TC-BACK-04** | Backing gagal tier sold out | `{"amount": 500000, "tier_id": 2}` (quota 0) | `422 Unprocessable Content`, kuota tier habis. |
| **TC-BACK-05** | Backing pada kampanye draf/review | `POST /api/campaigns/{draft_id}/back` | `422 Unprocessable Content`, kampanye tidak aktif. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Integritas Dana saat Terjadi Kegagalan Jaringan**
   - *Penyebab:* Proses penambahan saldo kampanye dan pengurangan kuota dieksekusi secara terpisah tanpa transaksi ACID.
   - *Solusi:* Controller membungkus keseluruhan operasi dalam blok `DB::transaction(function() { ... })` sehingga jika salah satu step gagal, status database kembali utuh (*rollback*).

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `POST /api/campaigns/{id}/back` | ❌ | ✅ | ✅ | ❌ |
| `GET /api/my-backings` | ❌ | ✅ | ✅ | ❌ |
