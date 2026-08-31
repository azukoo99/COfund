# Modul 2: Manajemen Kampanye (`campaigns.md`)

Dokumentasi komprehensif untuk subsistem Inisiasi, Pengelolaan, Pencarian, Filter Kategori, Pengajuan Review, dan Siklus Hidup Kampanye (*Campaign Lifecycle*) pada platform CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Manajemen Kampanye** menangani seluruh siklus hidup proyek penggalangan dana crowdfunding, mulai dari inisiasi draf (*draft*), pemutakhiran deskripsi dan target dana, penentuan deadline, pengajuan verifikasi ke administrator (*submit review*), pencarian proyek publik berbasis *slug* unik, filter berdasarkan kategori/status, hingga pemantauan persentase pencapaian dana dan sisa hari aktif.

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\CampaignController`<br>`App\Http\Controllers\CategoryController` | Menangani operasi CRUD kampanye, pencarian publik, pengunggahan cover/foto, dan pengajuan draf ke tahap review. |
| **Models** | `App\Models\Campaign`<br>`App\Models\Category` | Representasi entitas kampanye dan kategori; menghitung atribut dinamis (`percentage`, `days_left`), mengelola relasi ke tiers, images, backings, dan updates. |
| **Middleware** | `auth:sanctum`<br>`verified` | Memastikan hanya creator terdaftar dan terverifikasi yang dapat membuat atau memodifikasi draf kampanye miliknya. |

### Alur Kerja (Workflow)

```text
[CAMPAIGN LIFECYCLE WORKFLOW]

Creator                             Admin                               Platform / Backers
   │                                  │                                         │
   ├───> POST /api/campaigns          │                                         │
   │     (Status: DRAFT)              │                                         │
   │        │                         │                                         │
   │        ├── Tambah Foto/Tier      │                                         │
   │        └── PUT /api/campaigns    │                                         │
   │                                  │                                         │
   ├───> POST /submit-review ────────>│                                         │
   │     (Status: REVIEW)             │                                         │
   │                                  ├───> Review Dokumen & Target             │
   │                                  │        │                                │
   │                                  │        ├── POST /reject ──> (DRAFT)     │
   │                                  │        └── POST /approve ──────────────>│ (Status: ACTIVE)
   │                                  │                                         │   │
   │                                  │                                         │   ├── Backing Open
   │                                  │                                         │   └── Deadline Reached
   │                                  │                                         │        │
   │                                  │                                         │        ├── >= 100% -> SUCCESS (Disburse)
   │                                  │                                         │        └── < 100%  -> FAILED (Refund)
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── CampaignController.php
│   │       └── CategoryController.php
│   └── Models/
│       ├── Campaign.php
│       └── Category.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Daftar Kategori Publik
- **HTTP Method:** `GET`
- **Path:** `/api/categories`
- **Autentikasi / Middleware:** Publik
- **Deskripsi:** Mengambil semua kategori proyek aktif yang tersedia di platform.

#### Response Sukses (HTTP 200 OK)
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Teknologi & Inovasi",
      "slug": "teknologi-inovasi",
      "icon": "pi-microchip"
    },
    {
      "id": 2,
      "name": "Sosial & Komunitas",
      "slug": "sosial-komunitas",
      "icon": "pi-users"
    }
  ]
}
```

---

### 4.2. Katalog Kampanye Publik (Explore / Search / Filter)
- **HTTP Method:** `GET`
- **Path:** `/api/campaigns`
- **Autentikasi / Middleware:** Publik
- **Deskripsi:** Menampilkan daftar kampanye yang berstatus `active` dengan dukungan filter pencarian judul, filter kategori, pengurutan (*sorting*), dan paginasi.

#### Tabel Parameter Query
| Parameter | Posisi | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `search` | Query | `string` | Opsional | `nullable\|string` | Kata kunci pencarian judul/deskripsi |
| `category_id` | Query | `integer` | Opsional | `nullable\|exists:categories,id` | Filter berdasarkan ID kategori |
| `status` | Query | `string` | Opsional | `in:active,success,failed` | Filter status (default: `active`) |
| `sort` | Query | `string` | Opsional | `in:latest,popular,ending_soon` | Urutan: Terbaru, Terpopuler, Segera Berakhir |
| `page` | Query | `integer` | Opsional | `min:1` | Nomor halaman paginasi (default: `1`) |
| `per_page` | Query | `integer` | Opsional | `min:1\|max:50` | Jumlah data per halaman (default: `12`) |

#### Response Sukses (HTTP 200 OK)
```json
{
  "data": [
    {
      "id": 1,
      "title": "Smart Trash Bin Otomatis IoT",
      "slug": "smart-trash-bin-otomatis-iot",
      "description": "Tempat sampah cerdas pemilah sampah otomatis berbasis AI...",
      "target_amount": "50000000.00",
      "collected_amount": "37500000.00",
      "status": "active",
      "deadline": "2026-09-30",
      "percentage": 75,
      "days_left": 33,
      "category": {
        "id": 1,
        "name": "Teknologi & Inovasi"
      },
      "creator": {
        "id": 3,
        "name": "Tech Labs",
        "email": "creator@example.com"
      },
      "primary_image": {
        "id": 1,
        "url": "https://images.unsplash.com/photo-1518770660439-4636190af475",
        "is_primary": true
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/campaigns?page=1",
    "last": "http://localhost:8000/api/campaigns?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/campaigns?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 12,
    "to": 12,
    "total": 35
  }
}
```

---

### 4.3. Detail Kampanye Berdasarkan Slug
- **HTTP Method:** `GET`
- **Path:** `/api/campaigns/{slug}`
- **Autentikasi / Middleware:** Publik
- **Deskripsi:** Mengambil detail komprehensif suatu kampanye termasuk daftar reward tier, galeri foto, pembaruan kabar (*updates*), dan statistik pendanaan.

#### Response Sukses (HTTP 200 OK)
```json
{
  "campaign": {
    "id": 1,
    "title": "Smart Trash Bin Otomatis IoT",
    "slug": "smart-trash-bin-otomatis-iot",
    "description": "Tempat sampah cerdas pemilah sampah otomatis berbasis AI dan sensor induktif...",
    "target_amount": "50000000.00",
    "collected_amount": "37500000.00",
    "status": "active",
    "deadline": "2026-09-30",
    "video_url": "https://youtube.com/watch?v=sample",
    "percentage": 75,
    "days_left": 33,
    "backings_count": 42,
    "category": {
      "id": 1,
      "name": "Teknologi & Inovasi"
    },
    "creator": {
      "id": 3,
      "name": "Tech Labs"
    },
    "images": [
      {
        "id": 1,
        "url": "https://images.unsplash.com/photo-1518770660439-4636190af475",
        "is_primary": true
      }
    ],
    "tiers": [
      {
        "id": 1,
        "name": "Early Bird Supporter",
        "min_amount": "100000.00",
        "quota": 50,
        "remaining_quota": 8,
        "reward_description": "Merchandise stiker eksklusif + Sertifikat digital"
      }
    ]
  }
}
```

---

### 4.4. Pembuatan Kampanye Baru (Draft)
- **HTTP Method:** `POST`
- **Path:** `/api/campaigns`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Membuat draf kampanye baru oleh pengguna. Role pengguna otomatis tereskalasi menjadi `creator` jika sebelumnya adalah `backer`.

#### Tabel Parameter Body (JSON)
| Parameter | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `title` | `string` | Wajib | `required\|string\|max:100` | Judul proyek kampanye |
| `category_id` | `integer` | Wajib | `required\|exists:categories,id` | ID Kategori yang dipilih |
| `target_amount` | `numeric` | Wajib | `required\|numeric\|min:100000` | Target nominal penggalangan (Min Rp 100.000) |
| `deadline` | `date` | Wajib | `required\|date\|after:today` | Tanggal batas akhir kampanye |
| `description` | `string` | Wajib | `required\|string` | Narasi dan rincian alokasi anggaran proyek |
| `video_url` | `string` | Opsional | `nullable\|url` | Tautan video demonstrasi (YouTube/Vimeo) |

#### Request Payload
```json
{
  "title": "Robot Pembersih Sampah Sungai Otomatis",
  "category_id": 1,
  "target_amount": 25000000,
  "deadline": "2026-11-30",
  "description": "Inovasi robot amfibi bertenaga surya untuk membersihkan limbah plastik di aliran sungai perkotaan...",
  "video_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
}
```

#### Response Sukses (HTTP 201 Created)
```json
{
  "message": "Draf kampanye berhasil dibuat.",
  "campaign": {
    "id": 12,
    "user_id": 3,
    "category_id": 1,
    "title": "Robot Pembersih Sampah Sungai Otomatis",
    "slug": "robot-pembersih-sampah-sungai-otomatis",
    "target_amount": "25000000.00",
    "collected_amount": "0.00",
    "status": "draft",
    "deadline": "2026-11-30",
    "created_at": "2026-08-28T06:30:00.000000Z"
  }
}
```

---

### 4.5. Pemutakhiran Draf Kampanye
- **HTTP Method:** `PUT`
- **Path:** `/api/campaigns/{id}`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Memperbarui data kampanye. Hanya diperbolehkan jika kampanye masih berstatus `draft` dan dimiliki oleh creator yang bersangkutan.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Kampanye berhasil diperbarui.",
  "campaign": {
    "id": 12,
    "title": "Robot Pembersih Sampah Sungai Otomatis v2",
    "target_amount": "30000000.00",
    "status": "draft"
  }
}
```

---

### 4.6. Pengajuan Draf ke Admin (Submit Review)
- **HTTP Method:** `POST`
- **Path:** `/api/campaigns/{id}/submit-review`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Mengubah status kampanye dari `draft` menjadi `review` untuk ditinjau kelayakannya oleh Admin.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Kampanye berhasil diajukan untuk ditinjau oleh Administrator.",
  "campaign": {
    "id": 12,
    "status": "review"
  }
}
```

---

### 4.7. Penghapusan Draf Kampanye
- **HTTP Method:** `DELETE`
- **Path:** `/api/campaigns/{id}`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Menghapus draf kampanye beserta file foto dan reward tier terkait. Hanya berlaku untuk status `draft`.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Draf kampanye berhasil dihapus."
}
```

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `Campaign`
```json
{
  "id": 1,
  "user_id": 3,
  "category_id": 1,
  "title": "Smart Trash Bin Otomatis IoT",
  "slug": "smart-trash-bin-otomatis-iot",
  "description": "Tempat sampah cerdas pemilah sampah...",
  "target_amount": "50000000.00",
  "collected_amount": "37500000.00",
  "status": "active",
  "deadline": "2026-09-30",
  "video_url": "https://youtube.com/watch?v=sample",
  "percentage": 75,
  "days_left": 33,
  "created_at": "2026-08-01T00:00:00.000000Z",
  "updated_at": "2026-08-28T05:00:00.000000Z"
}
```

### Tabel Definisi Kolom Data
| Kolom | Tipe Database | Nullable | Deskripsi |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary key unik kampanye |
| `user_id` | `BIGINT UNSIGNED` | Tidak | Foreign key pemilik kampanye (`users.id`) |
| `category_id` | `BIGINT UNSIGNED` | Tidak | Foreign key kategori (`categories.id`) |
| `title` | `VARCHAR(100)` | Tidak | Judul proyek kampanye |
| `slug` | `VARCHAR(255)` | Tidak | Slug URL ramah SEO (unik) |
| `description` | `LONGTEXT` | Tidak | Konten narasi deskripsi proyek |
| `target_amount` | `DECIMAL(15,2)` | Tidak | Target nominal penggalangan dana |
| `collected_amount`| `DECIMAL(15,2)` | Tidak | Total dana terkumpul saat ini |
| `status` | `ENUM('draft','review','active','success','failed')` | Tidak | Status tahapan kampanye |
| `deadline` | `DATE` | Tidak | Tanggal berakhirnya masa penggalangan |
| `video_url` | `VARCHAR(255)` | Ya | URL video pitch proyek |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: POST /api/campaigns
pm.test("Status code is 201 Created", function () {
    pm.response.to.have.status(201);
});

pm.test("Campaign starts with DRAFT status", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.campaign.status).to.eql("draft");
    pm.expect(jsonData.campaign.slug).to.be.a('string');
    
    // Simpan id dan slug kampanye
    pm.environment.set("draft_campaign_id", jsonData.campaign.id);
    pm.environment.set("draft_campaign_slug", jsonData.campaign.slug);
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-CAMP-01** | Ambil daftar kampanye aktif publik | `GET /api/campaigns` | `200 OK`, array data terpaginasi dengan metadata. |
| **TC-CAMP-02** | Buat draf kampanye baru valid | Data title, target, deadline valid | `201 Created`, status `draft`. |
| **TC-CAMP-03** | Buat kampanye gagal target dana < 100.000 | `{"target_amount": 50000, ...}` | `422 Unprocessable Content`, target min 100.000. |
| **TC-CAMP-04** | Edit kampanye yang sudah berstatus active | `PUT /api/campaigns/1` (status active) | `403 Forbidden`, hanya status draft yang dapat diedit. |
| **TC-CAMP-05** | Submit review draf kampanye | `POST /api/campaigns/{id}/submit-review` | `200 OK`, status berubah menjadi `review`. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Error: `403 This action is unauthorized` saat update kampanye**
   - *Penyebab:* Pengguna yang login bukan pemilik kampanye (`user_id !== auth()->id()`) atau status kampanye bukan `draft`.
   - *Solusi:* Pastikan token yang digunakan milik creator pembuat kampanye dan periksa status kampanye di database.

2. **Slug duplikat menghasilkan error SQL saat pembuatan draf**
   - *Penyebab:* `Str::slug($title)` menghasilkan string yang sama persis dengan kampanye yang sudah ada sebelumnya.
   - *Solusi:* Controller menerapkan penambahan postfix acak/unik `slug-xxxx` jika terdeteksi duplikasi slug.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik | Backer | Creator (Owner) | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `GET /api/categories` | ✅ | ✅ | ✅ | ✅ |
| `GET /api/campaigns` | ✅ | ✅ | ✅ | ✅ |
| `GET /api/campaigns/{slug}` | ✅ | ✅ | ✅ | ✅ |
| `POST /api/campaigns` | ❌ | ✅ (Auto-upgrade) | ✅ | ❌ |
| `PUT /api/campaigns/{id}` | ❌ | ❌ | ✅ (Draft only) | ❌ |
| `DELETE /api/campaigns/{id}` | ❌ | ❌ | ✅ (Draft only) | ❌ |
| `POST /api/campaigns/{id}/submit-review` | ❌ | ❌ | ✅ (Draft only) | ❌ |
