# Modul 8: Notifikasi & Transaksi Platform (`transaction.md`)

Dokumentasi komprehensif untuk subsistem Notifikasi In-App Pengguna (*Notification Center*), Penandaan Status Baca (*Mark as Read*), dan Logging Transaksi Finansial Platform CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Notifikasi & Transaksi Platform** mengatur sistem pemberitahuan *real-time* dan *in-app notifications* bagi seluruh pengguna platform (Backer, Creator, Admin). Notifikasi dikirimkan otomatis ketika terjadi peristiwa kritis seperti: persetujuan/penolakan kampanye, penerimaan donasi masuk, posting kabar pembaruan proyek, kampanye mencapai target dana, atau pengembalian dana (*refund*) saat proyek gagal.

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\NotificationController` | Menyajikan daftar notifikasi pengguna, menghitung jumlah belum dibaca (*unread count*), menandai pesan terbaca, dan menghapus notifikasi. |
| **Models** | `App\Models\Notification`<br>`App\Models\User` | Menyimpan entri pesan notifikasi, relasi ke `User`, dan tracking timestamp `read_at`. |
| **Middleware** | `auth:sanctum` | Memastikan pengguna hanya dapat melihat dan mengelola notifikasi miliknya sendiri. |

### Alur Kerja (Workflow)

```text
[NOTIFICATION FLOW]

Platform Event (Backing Success / Campaign Approved / Update Posted)
   │
   ├───> Buat Record Notification (user_id, type, title, body, is_read: false)
   │        │
   │        └── Tersimpan di tabel 'notifications'
   │
Pengguna (Client App)
   │
   ├───> GET /api/notifications/unread-count ───> Return Badge Jumlah Belum Dibaca
   ├───> GET /api/notifications ────────────────> Tampilkan List Notifikasi
   ├───> PATCH /api/notifications/{id}/read ────> Set read_at = now()
   └───> PATCH /api/notifications/read-all ─────> Set semua read_at = now()
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── NotificationController.php
│   └── Models/
│       └── Notification.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Daftar Notifikasi Pengguna
- **HTTP Method:** `GET`
- **Path:** `/api/notifications`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mengambil semua daftar notifikasi milik pengguna yang sedang login.

#### Tabel Parameter Query
| Parameter | Posisi | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `unread_only` | Query | `boolean` | Opsional | `boolean\|in:0,1` | Filter hanya notifikasi yang belum dibaca (`1` = unread) |
| `page` | Query | `integer` | Opsional | `min:1` | Halaman paginasi |

#### Response Sukses (HTTP 200 OK)
```json
{
  "notifications": {
    "data": [
      {
        "id": 18,
        "type": "donation_received",
        "title": "Donasi Masuk!",
        "body": "Anda menerima donasi sebesar Rp 150.000 untuk proyek Smart Trash Bin Otomatis.",
        "data": {
          "campaign_id": 1,
          "amount": 150000
        },
        "read_at": null,
        "created_at": "2026-08-28T07:15:00.000000Z"
      }
    ],
    "current_page": 1,
    "total": 1
  }
}
```

---

### 4.2. Jumlah Notifikasi Belum Dibaca (Unread Count)
- **HTTP Method:** `GET`
- **Path:** `/api/notifications/unread-count`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mengambil angka agregat notifikasi yang belum dibuka untuk ditampilkan pada badge ikon lonceng navbar.

#### Response Sukses (HTTP 200 OK)
```json
{
  "unread_count": 3
}
```

---

### 4.3. Tandai Satu Notifikasi Terbaca
- **HTTP Method:** `PATCH`
- **Path:** `/api/notifications/{notification_id}/read`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mengubah timestamp `read_at` notifikasi terpilih menjadi waktu saat ini.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Notifikasi ditandai sebagai telah dibaca."
}
```

---

### 4.4. Tandai Semua Notifikasi Terbaca (Mark All As Read)
- **HTTP Method:** `PATCH`
- **Path:** `/api/notifications/read-all`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Memperbarui semua notifikasi yang belum terbaca milik pengguna menjadi telah dibaca sekaligus.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Semua notifikasi berhasil ditandai telah dibaca."
}
```

---

### 4.5. Hapus Notifikasi
- **HTTP Method:** `DELETE`
- **Path:** `/api/notifications/{notification_id}`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Menghapus satu pesan notifikasi dari daftar pengguna.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Notifikasi berhasil dihapus."
}
```

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `Notification`
```json
{
  "id": 18,
  "user_id": 3,
  "type": "campaign_approved",
  "title": "Kampanye Disetujui!",
  "body": "Selamat! Kampanye 'Smart Trash Bin' telah disetujui dan kini berstatus aktif.",
  "data": {
    "campaign_id": 1
  },
  "read_at": null,
  "created_at": "2026-08-28T07:00:00.000000Z"
}
```

### Tabel Definisi Kolom Data
| Kolom | Tipe Database | Nullable | Deskripsi |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary key unik notifikasi |
| `user_id` | `BIGINT UNSIGNED` | Tidak | Foreign key penerima notifikasi (`users.id`) |
| `type` | `VARCHAR(50)` | Tidak | Kategori event (`campaign_approved`, `donation_received`, `refund_processed`, dll.) |
| `title` | `VARCHAR(255)` | Tidak | Judul ringkas notifikasi |
| `body` | `TEXT` | Tidak | Isi teks pesan notifikasi |
| `data` | `JSON` | Ya | Metadata tambahan (misal ID kampanye / ID transaksi) |
| `read_at` | `TIMESTAMP` | Ya | Waktu notifikasi dibaca oleh pengguna (NULL jika belum) |
| `created_at` | `TIMESTAMP` | Ya | Waktu notifikasi diterbitkan |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: GET /api/notifications/unread-count
pm.test("Status code is 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Unread count is a valid non-negative integer", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('unread_count');
    pm.expect(jsonData.unread_count).to.be.at.least(0);
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-NTF-01** | Ambil seluruh notifikasi pengguna | `GET /api/notifications` | `200 OK`, daftar notifikasi terpaginasi. |
| **TC-NTF-02** | Filter notifikasi belum dibaca | `GET /api/notifications?unread_only=1` | `200 OK`, hanya record dengan `read_at == null`. |
| **TC-NTF-03** | Tandai satu notifikasi terbaca | `PATCH /api/notifications/{id}/read` | `200 OK`, `read_at` terisi timestamp. |
| **TC-NTF-04** | Tandai semua notifikasi terbaca | `PATCH /api/notifications/read-all` | `200 OK`, seluruh unread_count menjadi 0. |
| **TC-NTF-05** | Hapus notifikasi | `DELETE /api/notifications/{id}` | `200 OK`, record terhapus. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Notifikasi Milik Pengguna Lain Tidak Sengaja Terubah**
   - *Pencegahan:* Controller memvalidasi kepemilikan notifikasi dengan klausul `where('user_id', auth()->id())` sebelum mengeksekusi update atau delete.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `GET /api/notifications` | ❌ | ✅ | ✅ | ✅ |
| `GET /api/notifications/unread-count` | ❌ | ✅ | ✅ | ✅ |
| `PATCH /api/notifications/{id}/read` | ❌ | ✅ | ✅ | ✅ |
| `PATCH /api/notifications/read-all` | ❌ | ✅ | ✅ | ✅ |
| `DELETE /api/notifications/{id}` | ❌ | ✅ | ✅ | ✅ |
