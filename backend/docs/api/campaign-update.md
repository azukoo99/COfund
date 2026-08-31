# Modul 5: Kabar Terbaru & Pembaruan Kampanye (`campaign-update.md`)

Dokumentasi komprehensif untuk subsistem Posting Berita Perkembangan (*Campaign Progress Updates*) dan Pengiriman Notifikasi Siaran (*Broadcast Notifications*) kepada seluruh donatur pendukung proyek pada platform CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Kabar Terbaru Kampanye** memberikan sarana transparansi bagi creator untuk membagikan milestone, perkembangan riset, foto kemajuan produksi, maupun kendala teknis kepada para donatur pendukung proyek. Setiap posting update yang diterbitkan otomatis memicu notifikasi siaran ke seluruh backer terdaftar kampanye tersebut.

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\CampaignUpdateController` | Mengambil daftar kabar publik dan memproses pembuatan posting berita baru oleh creator. |
| **Models** | `App\Models\CampaignUpdate`<br>`App\Models\Campaign`<br>`App\Models\Notification` | Menyimpan narasi update dan membuat entri notifikasi ke setiap donatur yang terafiliasi dengan kampanye. |
| **Middleware** | `auth:sanctum`<br>`verified` | Memastikan hanya creator pemilik kampanye yang dapat mempublikasikan update. |

### Alur Kerja (Workflow)

```text
[CAMPAIGN UPDATE WORKFLOW]

Creator (Owner)                 CampaignUpdateController               Database (campaign_updates & notifications)
   │                                       │                                              │
   ├───> POST /campaigns/{id}/updates      │                                              │
   │     (content: "Progress update...") ─>│                                              │
   │                                       ├── Cek Ownership & Status == ACTIVE           │
   │                                       ├── Simpan campaign_updates ──────────────────>│
   │                                       ├── Ambil daftar user_id donatur kampanye      │
   │                                       ├── Loop: Insert record notifikasi massal ────>│
   │                                       └── Return HTTP 201 (Update Data)              │
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── CampaignUpdateController.php
│   └── Models/
│       ├── Campaign.php
│       ├── CampaignUpdate.php
│       └── Notification.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Daftar Kabar Terbaru Kampanye
- **HTTP Method:** `GET`
- **Path:** `/api/campaigns/{campaign_id}/updates`
- **Autentikasi / Middleware:** Publik
- **Deskripsi:** Mengambil semua riwayat pembaruan berita/kabar kampanye yang diurutkan dari yang paling baru.

#### Response Sukses (HTTP 200 OK)
```json
{
  "updates": [
    {
      "id": 1,
      "campaign_id": 1,
      "content": "Proses pencetakan 3D casing robot dan uji coba sensor ultrasonik berjalan sukses tanpa kendala!",
      "created_at": "2026-08-20T10:00:00.000000Z"
    }
  ]
}
```

---

### 4.2. Publikasikan Kabar Terbaru
- **HTTP Method:** `POST`
- **Path:** `/api/campaigns/{campaign_id}/updates`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Memposting berita pembaruan kampanye aktif. Sistem otomatis membroadcast notifikasi in-app ke semua donatur kampanye.

#### Tabel Parameter Body (JSON)
| Parameter | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `content` | `string` | Wajib | `required\|string\|min:10` | Konten berita perkembangan inovasi proyek (Min. 10 karakter) |

#### Request Payload
```json
{
  "content": "Halo para donatur! Hari ini kami telah menyelesaikan tahap perakitan sirkuit utama mikrokontroler dan sensor IoT."
}
```

#### Response Sukses (HTTP 201 Created)
```json
{
  "message": "Kabar terbaru berhasil diposting dan dibroadcast ke para donatur.",
  "update": {
    "id": 5,
    "campaign_id": 1,
    "content": "Halo para donatur! Hari ini kami telah menyelesaikan tahap perakitan sirkuit utama mikrokontroler dan sensor IoT.",
    "created_at": "2026-08-28T07:00:00.000000Z"
  }
}
```

#### Efek Samping (Side Effects)
- Record baru tersimpan di tabel `campaign_updates`.
- Record notifikasi baru bertipe `campaign_update` dibuat di tabel `notifications` untuk setiap donatur yang pernah melakukan backing pada kampanye ini.

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `CampaignUpdate`
```json
{
  "id": 1,
  "campaign_id": 1,
  "content": "Proses pencetakan 3D casing robot berjalan lancar...",
  "created_at": "2026-08-20T10:00:00.000000Z"
}
```

### Tabel Definisi Kolom Data
| Kolom | Tipe Database | Nullable | Deskripsi |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary key unik update |
| `campaign_id` | `BIGINT UNSIGNED` | Tidak | Foreign key kampanye induk (`campaigns.id`) |
| `content` | `LONGTEXT` | Tidak | Isi teks narasi kabar perkembangan |
| `created_at` | `TIMESTAMP` | Ya | Waktu publikasi kabar |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: POST /api/campaigns/{id}/updates
pm.test("Status code is 201 Created", function () {
    pm.response.to.have.status(201);
});

pm.test("Update contains valid content string", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.update.content).to.be.a('string');
    pm.expect(jsonData.update.content.length).to.be.at.least(10);
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-UPD-01** | Ambil daftar kabar kampanye publik | `GET /api/campaigns/1/updates` | `200 OK`, array riwayat kabar. |
| **TC-UPD-02** | Posting update valid pada kampanye aktif | `{"content":"Uji coba lapangan robot berhasil dilakukan..."}` | `201 Created`, notifikasi terkirim ke donatur. |
| **TC-UPD-03** | Posting update gagal konten terlalu pendek | `{"content":"Halo"}` | `422 Unprocessable Content`, min 10 karakter. |
| **TC-UPD-04** | Posting update pada kampanye yang bukan miliknya | Token creator lain | `403 Forbidden`, Unauthorized action. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Donatur Mengeluh Tidak Menerima Notifikasi Update**
   - *Penyebab:* Relasi query backer menyaring backing berstatus `completed`. Donatur dengan transaksi `pending` atau `refunded` sengaja tidak menerima notifikasi siaran.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator (Owner) | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `GET /api/campaigns/{id}/updates` | ✅ | ✅ | ✅ | ✅ |
| `POST /api/campaigns/{id}/updates` | ❌ | ❌ | ✅ (Active only) | ❌ |
