# Modul 3: Manajemen Paket Reward / Tier (`tier.md`)

Dokumentasi komprehensif untuk subsistem Pembuatan, Modifikasi, Pengecekan Kuota, dan Penghapusan Paket Reward (*Reward Tiers*) pada platform CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Manajemen Reward Tier** memungkinkan creator membuat skema penghargaan bertingkat (*tier levels*) untuk menarik minat calon donatur. Setiap tier menentukan batas donasi minimum, kuota slot yang tersedia (atau tak terbatas jika bernilai 0), sisa kuota yang terpotong otomatis saat backing sukses, dan deskripsi apresiasi reward yang akan diterima oleh donatur.

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\CampaignTierController` | Mengelola CRUD reward tier kampanye, memastikan hanya pemilik draf yang dapat mengubah tier. |
| **Models** | `App\Models\CampaignTier`<br>`App\Models\Campaign` | Menyimpan atribut tier, relasi ke `Campaign`, dan relasi ke riwayat transaksi donatur (`backings`). |
| **Middleware** | `auth:sanctum`<br>`verified` | Melindungi endpoint modifikasi tier dari akses tanpa otorisasi. |

### Alur Kerja (Workflow)

```text
[TIER MANAGEMENT WORKFLOW]

Creator (Owner)                      Backend API (CampaignTierController)            Database (campaign_tiers)
   │                                               │                                            │
   ├───> POST /campaigns/{id}/tiers                │                                            │
   │     (name, min_amount, quota, reward) ───────>│                                            │
   │                                               ├── Cek Ownership & Status == DRAFT          │
   │                                               ├── Set remaining_quota = quota              │
   │                                               ├── Simpan Tier Record ─────────────────────>│
   │                                               └── Return HTTP 201 (Tier Data)              │
   │                                                                                            │
Backer                                                                                          │
   │                                                                                            │
   └───> POST /campaigns/{id}/back (tier_id: X) ──>├── Cek remaining_quota > 0                 │
                                                   ├── Decrement remaining_quota ──────────────>│
                                                   └── Selesai (Backing Created)
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── CampaignTierController.php
│   └── Models/
│       ├── Campaign.php
│       └── CampaignTier.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Daftar Tier Kampanye
- **HTTP Method:** `GET`
- **Path:** `/api/campaigns/{campaign_id}/tiers`
- **Autentikasi / Middleware:** Publik
- **Deskripsi:** Mengambil semua paket reward tier yang tersedia untuk suatu proyek kampanye.

#### Response Sukses (HTTP 200 OK)
```json
{
  "tiers": [
    {
      "id": 1,
      "campaign_id": 1,
      "name": "Early Supporter",
      "min_amount": "50000.00",
      "quota": 100,
      "remaining_quota": 45,
      "reward_description": "Akses eksklusif discord & nama di credits proyek",
      "created_at": "2026-08-01T00:00:00.000000Z"
    },
    {
      "id": 2,
      "campaign_id": 1,
      "name": "VIP Prototype Backer",
      "min_amount": "500000.00",
      "quota": 10,
      "remaining_quota": 0,
      "reward_description": "1 Unit prototype fisik robot + kaos eksklusif",
      "created_at": "2026-08-01T00:00:00.000000Z"
    }
  ]
}
```

---

### 4.2. Tambah Reward Tier Baru
- **HTTP Method:** `POST`
- **Path:** `/api/campaigns/{campaign_id}/tiers`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Menambahkan reward tier baru ke dalam draf kampanye milik creator.

#### Tabel Parameter Body (JSON)
| Parameter | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `name` | `string` | Wajib | `required\|string\|max:255` | Nama judul paket reward tier |
| `min_amount` | `numeric` | Wajib | `required\|numeric\|min:10000` | Nominal donasi minimal untuk memilih tier ini |
| `quota` | `integer` | Wajib | `required\|integer\|min:0` | Kuota slot tersedia (`0` berarti tak terbatas / unlimited) |
| `reward_description` | `string` | Opsional | `nullable\|string` | Rincian kompensasi / apresiasi yang akan dikirimkan |

#### Request Payload
```json
{
  "name": "Paket Kolektor Spesial",
  "min_amount": 250000,
  "quota": 25,
  "reward_description": "Miniatur robot akrilik bertanda tangan tim pengembang + stiker hologram"
}
```

#### Response Sukses (HTTP 201 Created)
```json
{
  "message": "Tier berhasil ditambahkan.",
  "tier": {
    "id": 8,
    "campaign_id": 12,
    "name": "Paket Kolektor Spesial",
    "min_amount": "250000.00",
    "quota": 25,
    "remaining_quota": 25,
    "reward_description": "Miniatur robot akrilik bertanda tangan tim pengembang + stiker hologram"
  }
}
```

---

### 4.3. Pemutakhiran Data Tier
- **HTTP Method:** `PUT`
- **Path:** `/api/campaigns/{campaign_id}/tiers/{tier_id}`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Memperbarui nominal, kuota, atau deskripsi reward tier yang berstatus `draft`.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Tier berhasil diperbarui.",
  "tier": {
    "id": 8,
    "name": "Paket Kolektor Edisi Terbatas",
    "min_amount": "300000.00",
    "quota": 30,
    "remaining_quota": 30
  }
}
```

---

### 4.4. Penghapusan Tier
- **HTTP Method:** `DELETE`
- **Path:** `/api/campaigns/{campaign_id}/tiers/{tier_id}`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Menghapus reward tier dari kampanye (hanya diperbolehkan saat kampanye berstatus `draft`).

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Tier berhasil dihapus."
}
```

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `CampaignTier`
```json
{
  "id": 1,
  "campaign_id": 1,
  "name": "Early Supporter",
  "min_amount": "50000.00",
  "quota": 100,
  "remaining_quota": 45,
  "reward_description": "Akses eksklusif discord & sertifikat digital",
  "created_at": "2026-08-01T00:00:00.000000Z",
  "updated_at": "2026-08-28T05:00:00.000000Z"
}
```

### Tabel Definisi Kolom Data
| Kolom | Tipe Database | Nullable | Deskripsi |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary key unik reward tier |
| `campaign_id` | `BIGINT UNSIGNED` | Tidak | Foreign key kampanye induk (`campaigns.id`) |
| `name` | `VARCHAR(255)` | Tidak | Nama paket reward |
| `min_amount` | `DECIMAL(15,2)` | Tidak | Nominal donasi minimum untuk klaim tier ini |
| `quota` | `INT` | Tidak | Kuota maksimal yang dialokasikan (0 = unlimited) |
| `remaining_quota` | `INT` | Tidak | Kuota tersisa yang belum terklaim oleh donatur |
| `reward_description` | `TEXT` | Ya | Rincian fisik/digital reward yang dijanjikan creator |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: POST /api/campaigns/{id}/tiers
pm.test("Status code is 201 Created", function () {
    pm.response.to.have.status(201);
});

pm.test("Remaining quota equals initial quota", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.tier.remaining_quota).to.eql(jsonData.tier.quota);
    pm.environment.set("created_tier_id", jsonData.tier.id);
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-TIER-01** | Ambil daftar tier kampanye publik | `GET /api/campaigns/1/tiers` | `200 OK`, array tier lengkap. |
| **TC-TIER-02** | Tambah tier baru pada draf milik sendiri | Data valid (`min_amount >= 10000`) | `201 Created`, tier tersimpan. |
| **TC-TIER-03** | Tambah tier gagal `min_amount < 10000` | `{"min_amount": 5000, ...}` | `422 Unprocessable Content`, min nominal 10.000. |
| **TC-TIER-04** | Modifikasi tier pada kampanye orang lain | Token pengguna lain | `403 Forbidden`, Unauthorized. |
| **TC-TIER-05** | Hapus tier pada kampanye yang sudah active | `DELETE /api/campaigns/{active_id}/tiers/{id}` | `403 Forbidden`, hanya status draft yang dapat dihapus. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Kuota Terkunci saat Backing Gagal di Tengah Transaksi**
   - *Penyebab:* Pengurangan kuota tier harus dibungkus dalam `DB::transaction()`. Jika proses backing gagal sebelum commit, kuota otomatis di-rollback oleh database.

2. **Donatur Memilih Tier yang Sisa Kuotanya 0 (`Sold Out`)**
   - *Penyebab:* Kuota habis terisi oleh donatur lain.
   - *Solusi:* Backend menolak request backing dengan HTTP `422` pesan `"Kuota tier reward ini telah habis."`.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator (Owner) | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `GET /api/campaigns/{id}/tiers` | ✅ | ✅ | ✅ | ✅ |
| `POST /api/campaigns/{id}/tiers` | ❌ | ❌ | ✅ (Draft only) | ❌ |
| `PUT /api/campaigns/{id}/tiers/{tier}` | ❌ | ❌ | ✅ (Draft only) | ❌ |
| `DELETE /api/campaigns/{id}/tiers/{tier}` | ❌ | ❌ | ✅ (Draft only) | ❌ |
