# Modul 9: Dashboard Pengguna & Analitik Creator (`creator.md`)

Dokumentasi komprehensif untuk subsistem Agregasi Metrik Creator (*Funding Analytics*), Riwayat Performa Kampanye, dan Dashboard Ringkasan Portofolio Donatur (*Backer Portfolio*) pada platform CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Dashboard Pengguna** menyediakan antarmuka analitik dan agregasi data khusus untuk dua tipe peran utama:
1. **Dashboard Creator:** Menyajikan total akumulasi dana terkumpul, total donatur, daftar kampanye aktif/draf, tren grafik pendanaan harian (*daily funding chart*), dan saldo dompet virtual yang dapat ditarik.
2. **Dashboard Backer:** Menyajikan total dana yang telah didonasikan, jumlah kampanye yang didukung, jumlah refund yang telah diterima, dan riwayat transaksi kontribusi.

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\DashboardController` | Menghitung kalkulasi SQL agregat (SUM, COUNT, GROUP BY DATE) dan memformat response statistik untuk Creator & Backer. |
| **Models** | `App\Models\Campaign`<br>`App\Models\Backing`<br>`App\Models\User` | Query data relasional untuk menghitung metrik finansial dan histori transaksi pengguna. |
| **Middleware** | `auth:sanctum` | Memastikan data dashboard hanya disajikan kepada pemilik akun yang bersangkutan. |

### Alur Kerja (Workflow)

```text
[DASHBOARD ANALYTICS FLOW]

Creator Request (GET /api/dashboard/creator)
   │
   ├───> Hitung summary.total_collected (SUM campaigns.collected_amount WHERE user_id)
   ├───> Hitung summary.total_backers (COUNT DISTINCT backings.user_id)
   ├───> Hitung summary.active_campaigns_count (COUNT WHERE status = 'active')
   ├───> Ambil wallet_balance (users.wallet_balance)
   ├───> Agregasi daily_funding 30 hari terakhir (SUM backings.amount GROUP BY DATE)
   ├───> Ambil list seluruh kampanye creator beserta progress & days_left
   └───> Return HTTP 200 (Dashboard Dataset)
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── DashboardController.php
│   └── Models/
│       ├── Backing.php
│       ├── Campaign.php
│       └── User.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Dashboard Creator & Analitik Pendanaan
- **HTTP Method:** `GET`
- **Path:** `/api/dashboard/creator`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mengambil ringkasan metrik statistik finansial, tren donasi harian 30 hari terakhir, dan seluruh daftar kampanye milik creator.

#### Response Sukses (HTTP 200 OK)
```json
{
  "summary": {
    "total_collected": "87500000.00",
    "total_backers": 65,
    "active_campaigns_count": 2,
    "wallet_balance": "47500000.00"
  },
  "daily_funding": [
    {
      "date": "2026-08-27",
      "total_amount": "2500000.00"
    },
    {
      "date": "2026-08-28",
      "total_amount": "1500000.00"
    }
  ],
  "campaigns": [
    {
      "id": 1,
      "title": "Smart Trash Bin Otomatis IoT",
      "slug": "smart-trash-bin-otomatis-iot",
      "status": "active",
      "target_amount": "50000000.00",
      "collected_amount": "37500000.00",
      "collected_percentage": 75,
      "days_left": 33,
      "category": {
        "id": 1,
        "name": "Teknologi & Inovasi"
      }
    }
  ]
}
```

---

### 4.2. Dashboard Ringkasan Donatur (Backer)
- **HTTP Method:** `GET`
- **Path:** `/api/dashboard/backer`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mengambil metrik total donasi yang telah disalurkan, jumlah proyek yang didukung, refund yang diterima, dan riwayat transaksi backing terpaginasi.

#### Response Sukses (HTTP 200 OK)
```json
{
  "summary": {
    "total_donated": "1250000.00",
    "total_campaigns_backed": 4,
    "total_refunded": "250000.00"
  },
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

### Sample JSON: `CreatorDashboardResponse`
```json
{
  "summary": {
    "total_collected": "87500000.00",
    "total_backers": 65,
    "active_campaigns_count": 2,
    "wallet_balance": "47500000.00"
  },
  "daily_funding": [],
  "campaigns": []
}
```

### Tabel Definisi Matriks Agregasi
| Field | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `summary.total_collected` | `string (decimal)` | Akumulasi dana yang terkumpul dari seluruh proyek creator |
| `summary.total_backers` | `integer` | Jumlah total donatur unik yang mendanai proyek creator |
| `summary.active_campaigns_count` | `integer` | Jumlah kampanye milik creator yang sedang aktif |
| `summary.wallet_balance` | `string (decimal)` | Saldo dompet virtual creator yang siap ditarik |
| `daily_funding[].date` | `string (YYYY-MM-DD)` | Tanggal pencatatan donasi harian |
| `daily_funding[].total_amount` | `string (decimal)` | Total nominal donasi yang masuk pada tanggal tersebut |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: GET /api/dashboard/creator
pm.test("Status code is 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Dashboard contains valid summary metrics", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('summary');
    pm.expect(jsonData.summary).to.have.property('total_collected');
    pm.expect(jsonData.summary).to.have.property('total_backers');
    pm.expect(jsonData.campaigns).to.be.an('array');
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-DSH-01** | Ambil data dashboard creator akun terdaftar | `GET /api/dashboard/creator` (Token Creator) | `200 OK`, dataset analitik & list kampanye. |
| **TC-DSH-02** | Ambil data dashboard backer | `GET /api/dashboard/backer` (Token Backer) | `200 OK`, dataset total donasi & list backing. |
| **TC-DSH-03** | Akses dashboard tanpa autentikasi | `GET /api/dashboard/creator` tanpa header token | `401 Unauthorized`. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Optimasi Query Performa Dashboard Creator**
   - *Pola:* Pengambilan data kampanye menyertakan *eager loading* kategori (`with('category')`) dan perhitungan persentase dilakukan pada level SQL / accessor untuk mencegah masalah *N+1 query*.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `GET /api/dashboard/creator` | ❌ | ✅ | ✅ | ✅ |
| `GET /api/dashboard/backer` | ❌ | ✅ | ✅ | ✅ |
