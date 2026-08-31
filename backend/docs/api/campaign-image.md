# Modul 4: Galeri & Cover Gambar Kampanye (`campaign-image.md`)

Dokumentasi komprehensif untuk subsistem Pengunggahan Foto Galeri (*Multi-Image Upload*), Pengaturan Gambar Utama (*Primary Cover*), dan Penghapusan Media Kampanye pada platform CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Galeri & Gambar Kampanye** mengelola aset visual kampanye crowdfunding. Mendukung pengunggahan gambar baik melalui berkas lokal (*multipart/form-data*) maupun tautan URL langsung (*remote image URL*), pembatasan kuota maksimal 5 gambar per kampanye, pengaturan satu gambar sebagai *primary cover* yang tampil di katalog kartu publik, dan penghapusan foto dari penyimpanan (*storage*).

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\CampaignController` (`uploadImages`, `deleteImage`, `setPrimaryImage`) | Memvalidasi file gambar/URL, menyimpan file ke `storage/app/public/campaigns`, membuat record `campaign_images`, dan mengatur status `is_primary`. |
| **Models** | `App\Models\CampaignImage`<br>`App\Models\Campaign` | Menyimpan URL path gambar, relasi ke `Campaign`, dan scope gambar utama (`is_primary = true`). |
| **Storage & Facades** | `Illuminate\Support\Facades\Storage` | Mengelola file upload di disk public Laravel. |

### Alur Kerja (Workflow)

```text
[IMAGE MANAGEMENT WORKFLOW]

Creator                             Backend (CampaignController)               Storage / DB
   │                                             │                                  │
   ├───> POST /campaigns/{id}/images             │                                  │
   │     (Files: image[], URLs: image_urls[]) ──>│                                  │
   │                                             ├── Cek Total Gambar Saat Ini <= 5 │
   │                                             ├── Upload & Simpan File Lokal ───>│ Storage Disk
   │                                             ├── Simpan Record campaign_images ─>│ Database
   │                                             └── Return HTTP 200 (Images Array) │
   │                                                                                │
   ├───> PATCH /campaigns/{id}/images/{img}/primary                                 │
   │                                             ├── Set all is_primary = false ────>│ Database
   │                                             ├── Set this is_primary = true ────>│ Database
   │                                             └── Return HTTP 200 OK             │
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── CampaignController.php
│   └── Models/
│       ├── Campaign.php
│       └── CampaignImage.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Unggah Gambar Galeri Kampanye
- **HTTP Method:** `POST`
- **Path:** `/api/campaigns/{campaign_id}/images`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Content-Type:** `multipart/form-data` atau `application/json`
- **Deskripsi:** Mengunggah hingga 5 gambar untuk galeri kampanye draf.

#### Tabel Parameter Request
| Parameter | Posisi | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `images` | Body (Form-data) | `array of files` | Opsional | `array\|max:5` | File gambar biner (`jpeg, png, jpg, gif, webp`, max: 2048KB per file) |
| `images.*` | File | `file` | Opsional | `image\|mimes:jpeg,png,jpg,gif,webp\|max:2048` | Spesifikasi validasi file gambar |
| `image_urls` | Body (JSON/Form) | `array of string` | Opsional | `array\|max:5` | Kumpulan tautan URL gambar eksternal (misal dari Unsplash) |
| `image_urls.*` | Body | `string (URL)` | Opsional | `url` | Format URL valid |

#### Request Example (JSON)
```json
{
  "image_urls": [
    "https://images.unsplash.com/photo-1518770660439-4636190af475",
    "https://images.unsplash.com/photo-1581092160607-ee22621dd758"
  ]
}
```

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Gambar berhasil diunggah.",
  "images": [
    {
      "id": 10,
      "campaign_id": 12,
      "url": "https://images.unsplash.com/photo-1518770660439-4636190af475",
      "is_primary": true
    },
    {
      "id": 11,
      "campaign_id": 12,
      "url": "https://images.unsplash.com/photo-1581092160607-ee22621dd758",
      "is_primary": false
    }
  ]
}
```

---

### 4.2. Tetapkan Sebagai Gambar Utama (Primary Cover)
- **HTTP Method:** `PATCH`
- **Path:** `/api/campaigns/{campaign_id}/images/{image_id}/primary`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Menjadikan gambar terpilih sebagai thumbnail utama kartu kampanye dan me-reset gambar lain menjadi bukan utama.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Gambar utama berhasil diperbarui."
}
```

---

### 4.3. Hapus Gambar Galeri
- **HTTP Method:** `DELETE`
- **Path:** `/api/campaigns/{campaign_id}/images/{image_id}`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Menghapus gambar dari database dan menghapus file biner dari storage jika merupakan berkas lokal.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Gambar berhasil dihapus."
}
```

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `CampaignImage`
```json
{
  "id": 1,
  "campaign_id": 1,
  "url": "https://images.unsplash.com/photo-1518770660439-4636190af475",
  "is_primary": true,
  "created_at": "2026-08-01T00:00:00.000000Z"
}
```

### Tabel Definisi Kolom Data
| Kolom | Tipe Database | Nullable | Deskripsi |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary key unik gambar |
| `campaign_id` | `BIGINT UNSIGNED` | Tidak | Foreign key kampanye induk (`campaigns.id`) |
| `url` | `VARCHAR(255)` | Tidak | Path relatif storage lokal atau URL absolut eksternal |
| `is_primary` | `BOOLEAN` | Tidak | Penanda gambar cover utama (default: `false`) |
| `created_at` | `TIMESTAMP` | Ya | Waktu pengunggahan gambar |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: POST /api/campaigns/{id}/images
pm.test("Status code is 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Images array returned with is_primary flag", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.images).to.be.an('array');
    pm.expect(jsonData.images.length).to.be.above(0);
    pm.environment.set("uploaded_image_id", jsonData.images[0].id);
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-IMG-01** | Unggah URL gambar valid | `{"image_urls":["https://via.placeholder.com/600"]}` | `200 OK`, gambar tersimpan, gambar pertama menjadi primary jika belum ada cover. |
| **TC-IMG-02** | Unggah gambar melebihi batas 5 foto | Kirim 6 gambar sekaligus | `422 Unprocessable Content`, maksimal 5 gambar per kampanye. |
| **TC-IMG-03** | Set gambar lain menjadi primary cover | `PATCH /api/campaigns/1/images/2/primary` | `200 OK`, flag `is_primary` gambar 2 menjadi `true` dan gambar 1 menjadi `false`. |
| **TC-IMG-04** | Hapus gambar kampanye draf | `DELETE /api/campaigns/1/images/2` | `200 OK`, record terhapus dari database. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Gambar Lokal Tidak Tampil di Frontend (Error 404 pada URL Gambar)**
   - *Penyebab:* Symlink storage Laravel belum dibuat.
   - *Solusi:* Jalankan perintah `php artisan storage:link` di direktori backend.

2. **Error `413 Request Entity Too Large` dari Web Server Nginx/Apache**
   - *Penyebab:* Batas `client_max_body_size` pada server web lebih kecil dari ukuran file gambar yang diunggah.
   - *Solusi:* Naikkan `upload_max_filesize = 10M` dan `post_max_size = 10M` pada `php.ini`.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator (Owner) | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `POST /api/campaigns/{id}/images` | ❌ | ❌ | ✅ (Draft only) | ❌ |
| `PATCH /api/campaigns/{id}/images/{img}/primary` | ❌ | ❌ | ✅ (Draft only) | ❌ |
| `DELETE /api/campaigns/{id}/images/{img}` | ❌ | ❌ | ✅ (Draft only) | ❌ |
