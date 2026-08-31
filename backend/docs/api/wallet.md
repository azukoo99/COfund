# Modul 7: Dompet Virtual & Penarikan Dana (`wallet.md`)

Dokumentasi komprehensif untuk subsistem Saldo Dompet Virtual (*Virtual Wallet*), Pencairan Dana Kampanye Sukses (*Disbursement*), Pengembalian Dana Kampanye Gagal (*Refund*), dan Simulasi Penarikan Dana (*Withdrawal*) pada platform CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Dompet Virtual & Penarikan Dana** mengatur sirkulasi saldo internal pengguna. Saldo dompet bertambah ketika creator menerima pencairan dana kampanye yang sukses (setelah dipotong *platform fee* 5%) atau ketika backer menerima pengembalian dana (*refund* 100%) akibat kampanye yang didukung gagal mencapai target. Pengguna dapat mengajukan penarikan saldo virtual (*withdraw*) ke rekening bank lokal yang diverifikasi.

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\TransactionController`<br>`App\Http\Controllers\DashboardController` (`withdraw`) | Mengambil mutasi saldo dan memvalidasi permintaan penarikan dana ke rekening bank. |
| **Models** | `App\Models\Transaction`<br>`App\Models\User` | Menyimpan log mutasi debit/kredit, menghitung saldo aktif pengguna, dan validasi limit saldo. |
| **Queue Jobs** | `App\Jobs\DisburseCampaignJob`<br>`App\Jobs\RefundBackersJob` | Menjalankan proses otomatis penambahan saldo creator (disbursement) atau saldo backer (refund) saat status kampanye selesai. |
| **Middleware** | `auth:sanctum` | Memastikan hanya pemilik akun yang dapat melihat saldo dan melakukan penarikan. |

### Alur Kerja (Workflow)

```text
[VIRTUAL WALLET & WITHDRAW FLOW]

1. Penambahan Saldo:
   - Kampanye Sukses ──> DisburseCampaignJob ──> Kredit Saldo Creator (95%) + Potong Fee (5%)
   - Kampanye Gagal  ──> RefundBackersJob    ──> Kredit Saldo Seluruh Backer (100%)

2. Penarikan Saldo (Withdraw):
User                                DashboardController                        Database (DB::transaction)
 │                                           │                                              │
 ├───> POST /api/me/withdraw                 │                                              │
 │     (amount, bank_name, account_number) ──>│                                             │
 │                                           ├── 1. Validasi: amount >= 10.000              │
 │                                           ├── 2. Validasi: user.wallet_balance >= amount │
 │                                           │                                              │
 │                                           ├── Potong user.wallet_balance ───────────────>│
 │                                           ├── Catat transaksi tipe 'withdraw' ──────────>│
 │                                           └── Return HTTP 200 (Withdraw Success)         │
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── DashboardController.php
│   │       └── TransactionController.php
│   ├── Jobs/
│   │   ├── DisburseCampaignJob.php
│   │   └── RefundBackersJob.php
│   └── Models/
│       ├── Transaction.php
│       └── User.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Cek Saldo & Mutasi Transaksi Saya
- **HTTP Method:** `GET`
- **Path:** `/api/me/balance`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mengambil saldo virtual terkini dan riwayat seluruh mutasi transaksi (pembayaran backing, pencairan dana, refund, penarikan) dengan filter tipe dan rentang tanggal.

#### Tabel Parameter Query
| Parameter | Posisi | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `type` | Query | `string` | Opsional | `in:payment,disbursement,refund,platform_fee,withdraw` | Filter berdasarkan tipe mutasi transaksi |
| `from` | Query | `date` | Opsional | `date` | Batas awal tanggal transaksi (`YYYY-MM-DD`) |
| `to` | Query | `date` | Opsional | `date\|after_or_equal:from` | Batas akhir tanggal transaksi (`YYYY-MM-DD`) |
| `page` | Query | `integer` | Opsional | `min:1` | Halaman paginasi (default: `1`) |

#### Response Sukses (HTTP 200 OK)
```json
{
  "balance": "47500000.00",
  "transactions": {
    "data": [
      {
        "id": 105,
        "type": "disbursement",
        "amount": "47500000.00",
        "reference_number": "DISB-CAMP-1-1724830000",
        "created_at": "2026-08-28T05:00:00.000000Z"
      },
      {
        "id": 106,
        "type": "platform_fee",
        "amount": "2500000.00",
        "reference_number": "FEE-CAMP-1-1724830000",
        "created_at": "2026-08-28T05:00:00.000000Z"
      }
    ],
    "current_page": 1,
    "total": 2
  }
}
```

---

### 4.2. Penarikan Saldo (Withdraw) ke Rekening Bank
- **HTTP Method:** `POST`
- **Path:** `/api/me/withdraw`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mengajukan penarikan saldo virtual ke nomor rekening bank lokal yang dituju.

#### Tabel Parameter Body (JSON)
| Parameter | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `amount` | `numeric` | Wajib | `required\|numeric\|min:10000` | Nominal penarikan (Min. Rp 10.000) |
| `bank_name` | `string` | Wajib | `required\|string\|max:50` | Nama bank tujuan (misal: BCA, Mandiri, BRI, BNI, BSI) |
| `account_number` | `string` | Wajib | `required\|string\|max:50` | Nomor rekening bank tujuan penarikan |

#### Request Payload
```json
{
  "amount": 5000000,
  "bank_name": "BCA",
  "account_number": "8801234567"
}
```

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Permintaan penarikan dana berhasil diproses.",
  "withdraw": {
    "amount": "5000000.00",
    "bank_name": "BCA",
    "account_number": "8801234567",
    "remaining_balance": "42500000.00"
  }
}
```

#### Tabel Error Handling
| HTTP Code | Body JSON | Kondisi |
| :--- | :--- | :--- |
| `422 Unprocessable Content` | `{"message":"Saldo tidak mencukupi untuk melakukan penarikan ini."}` | Nominal `amount` lebih besar daripada `wallet_balance` akun. |
| `422 Unprocessable Content` | `{"message":"Nominal penarikan minimal adalah Rp 10.000."}` | `amount` kurang dari 10.000. |

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `Transaction`
```json
{
  "id": 105,
  "user_id": 3,
  "campaign_id": 1,
  "type": "disbursement",
  "amount": "47500000.00",
  "reference_number": "DISB-CAMP-1-1724830000",
  "created_at": "2026-08-28T05:00:00.000000Z"
}
```

### Tabel Definisi Kolom Data
| Kolom | Tipe Database | Nullable | Deskripsi |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary key unik transaksi |
| `user_id` | `BIGINT UNSIGNED` | Tidak | Foreign key pemilik akun (`users.id`) |
| `campaign_id` | `BIGINT UNSIGNED` | Ya | Foreign key kampanye terkait (`campaigns.id`) |
| `type` | `ENUM('payment','disbursement','refund','platform_fee','withdraw')` | Tidak | Jenis klasifikasi mutasi keuangan |
| `amount` | `DECIMAL(15,2)` | Tidak | Besaran nominal transaksi |
| `reference_number`| `VARCHAR(255)` | Tidak | Nomor referensi audit unik transaksi |
| `created_at` | `TIMESTAMP` | Ya | Waktu pencatatan mutasi transaksi |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: GET /api/me/balance
pm.test("Status code is 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Response contains numeric balance", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('balance');
    pm.expect(jsonData.transactions.data).to.be.an('array');
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-WAL-01** | Ambil saldo dan daftar mutasi | `GET /api/me/balance` | `200 OK`, saldo & riwayat mutasi terpaginasi. |
| **TC-WAL-02** | Filter mutasi berdasarkan tipe | `GET /api/me/balance?type=refund` | `200 OK`, hanya menampilkan mutasi refund. |
| **TC-WAL-03** | Penarikan dana saldo mencukupi | `{"amount": 50000, "bank_name":"BCA", "account_number":"1234"}` | `200 OK`, saldo berkurang 50.000. |
| **TC-WAL-04** | Penarikan dana gagal saldo kurang | `{"amount": 999999999, ...}` | `422 Unprocessable Content`, saldo tidak mencukupi. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Perhitungan Saldo Virtual vs Riwayat Transaksi**
   - *Pola Desain:* Kolom `wallet_balance` pada tabel `users` bertindak sebagai cache saldo cepat, sedangkan tabel `transactions` bertindak sebagai *ledger of truth*. Nilai saldo diverifikasi berkala agar selalu sinkron dengan kalkulasi debit/kredit mutasi.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `GET /api/me/balance` | ❌ | ✅ | ✅ | ✅ |
| `POST /api/me/withdraw` | ❌ | ✅ | ✅ | ❌ |
