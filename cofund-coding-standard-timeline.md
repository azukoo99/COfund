# CoFund — Standarisasi Kode & Timeline Pelatihan

**Pelatihan WebDev — Magang**
v1.0 | 2 Juli 2026

> Dokumen ini berisi standar kode wajib (Laravel & Vue.js) serta timeline rekomendasi 7 hari untuk pelatihan proyek CoFund. Gunakan sebagai acuan konsistensi coding sekaligus pacing pengerjaan.

---

## Agenda

**Section 1 — Standarisasi Kode**
- Penamaan file, class & variabel Laravel
- Struktur folder & konvensi route
- FormRequest (opsional, direkomendasikan)
- Penamaan file & komponen Vue
- Props, emits & composables
- Service layer — axios per modul
- Pinia store standards
- Library stack: PrimeVue & PrimeIcons

**Section 2 — Timeline Pelatihan**
- Overview timeline 1 minggu (rekomendasi)
- Target Backend per hari
- Target Frontend per hari
- Mock JSON API untuk peserta FE only
- Checklist akhir pelatihan

---

# Section 1 — Standarisasi Kode

## 1.1 Laravel — Penamaan File & Class

| Tipe | Pola | Benar ✓ | Salah ✗ |
|---|---|---|---|
| Model | Singular + PascalCase | `Campaign` | `Campaigns` ✗ |
| Controller | PascalCase + `Controller` | `CampaignController` | `campaignController` ✗ |
| Request | Verb + Noun + `Request` | `StoreCampaignRequest` | `CampaignRequest` ✗ |
| Service | Noun + `Service` | `CampaignService` | `campaignSvc` ✗ |
| Job | Action + `Job` | `RefundBackersJob` | `refundJob` ✗ |
| Event | PascalCase past tense | `CampaignFunded` | `campaign_funded` ✗ |
| Migration | snake_case + deskriptif | `create_campaigns_table` | `campaigns` ✗ |

## 1.2 Laravel — Penamaan Variabel & Method

**✓ BENAR**
```php
// Variabel — camelCase
$campaignId = 1;
$totalAmount = 500000;
$isActive = true;

// Collection — plural
$campaigns = Campaign::all();
$backings = $campaign->backings;

// Method — verb + noun
public function getCampaign(int $id) {}
public function storeBacking() {}
public function calculateFee(): float {}

// Boolean — is/has prefix
public function isExpired(): bool {}
public function hasReachedTarget(): bool {}
```

**✗ SALAH**
```php
// Hindari snake_case untuk variabel
$campaign_id = 1;
$total_amount = 500000;
$is_active = true;

// Jangan singular untuk collection
$campaign = Campaign::all();
$backing = $campaign->backings;

// Jangan nama tidak jelas
public function process() {}
public function save() {}
public function calc() {}

// Jangan tanpa prefix boolean
public function expired(): bool {}
public function target(): bool {}
```

## 1.3 Laravel — Struktur Folder

Letakkan file di tempat yang tepat — jangan semua ditumpuk di Controllers.

```
app/
  Models/                 ← Eloquent models
  Http/
    Controllers/
      Api/                ← API controllers
    Requests/             ← Form validation*
    Resources/            ← API response shape
  Services/               ← Business logic
  Jobs/                   ← Queue jobs
  Events/                 ← Domain events
  Listeners/

database/migrations/
routes/api.php

* Requests/ opsional tapi disarankan
```

**Tanggung jawab tiap layer:**

- **Models** — hanya relasi, scopes, casts, accessor. **Tidak ada business logic.**
- **Controllers** — terima request → delegate ke Service → return response. Validasi boleh di sini jika sederhana.
- **Services** — semua business logic ada di sini. Injectable via constructor atau static.
- **Requests (opsional)** — direkomendasikan untuk validasi kompleks atau yang dipakai ulang di banyak endpoint.

## 1.4 Laravel — Route & API Convention

**Route Convention**
```php
Route::prefix('v1')->group(function () {
    // apiResource = index, show, store, update, destroy
    Route::apiResource('campaigns', CampaignController::class);

    Route::post('campaigns/{id}/back', [BackingController::class, 'store']);
});
```

**Route Naming**

| Route Name | Method | Path |
|---|---|---|
| `campaigns.index` | GET | `/campaigns` |
| `campaigns.show` | GET | `/campaigns/{id}` |
| `campaigns.store` | POST | `/campaigns` |
| `campaigns.update` | PUT | `/campaigns/{id}` |
| `backings.store` | POST | `/campaigns/{id}/back` |

**Response Format (konsisten di semua endpoint)**
```php
// Success response
return response()->json([
    'success' => true,
    'message' => 'Campaign created',
    'data'    => new CampaignResource($campaign),
], 201);

// Error response
return response()->json([
    'success' => false,
    'message' => 'Validation failed',
    'errors'  => $validator->errors(),
], 422);

// Paginated
return response()->json([
    'success' => true,
    'data'    => CampaignResource::collection($campaigns->paginate(10)),
    'meta'    => ['total' => ..., 'page' => ...],
]);
```

> 💡 **Validasi**: boleh di `FormRequest` (rekomendasi untuk validasi reusable) atau langsung `$request->validate([...])` di controller — pilih yang paling sesuai konteks.

## 1.5 Vue — Penamaan File & Komponen

| Tipe | Pola | Benar ✓ | Salah ✗ |
|---|---|---|---|
| Component | PascalCase.vue | `CampaignCard.vue` | `campaignCard.vue` ✗ |
| Page | PascalCase.vue (pages/) | `CampaignDetail.vue` | `campaignDetail.vue` ✗ |
| Layout | PascalCase.vue | `MainLayout.vue` | `mainlayout.vue` ✗ |
| Composable | use + camelCase.js | `useCampaign.js` | `campaignHelper.js` ✗ |
| Store | use + Noun + Store.js | `useCampaignStore.js` | `campaignStore.js` ✗ |
| Service | camelCase + Service.js | `campaignService.js` | `CampaignService.js` ✗ |
| Utils | camelCase.js | `formatCurrency.js` | `FormatCurrency.js` ✗ |

## 1.6 Vue — Props, Emits & Composables

**Props**
```js
// camelCase di script
const props = defineProps({
  campaignId: Number,
  isActive: Boolean,
  targetAmount: {
    type: Number,
    required: true,
  },
})
// kebab-case di template:
// :campaign-id="id"
```

**Emits**
```js
// kebab-case untuk nama emit
const emit = defineEmits([
  'backing-submitted',
  'update:modelValue',
  'campaign-updated',
])

// Cara trigger:
emit('backing-submitted', {
  amount: 50000,
  tier: selectedTier,
})
```

**Composable**
```js
// Selalu prefix "use"
export function useCampaign() {
  const store = useCampaignStore()
  const isLoading = ref(false)

  async function fetchOne(id) {
    isLoading.value = true
    await store.fetch(id)
    isLoading.value = false
  }

  return { isLoading, fetchOne }
}
```

**Reactive Variables**
```js
const isLoading = ref(false)       // boolean — is/has prefix
const campaigns = ref([])          // collection — plural noun
const totalAmount = computed(() =>
  backings.value.reduce((s, b) => s + b.amount, 0)
)
```

Aturan tambahan:
- Props: camelCase di script, kebab-case di template.
- Emits: kebab-case selalu, hindari camelCase.
- Jangan taruh API call di komponen — pakai composable.
- Composable = satu tanggung jawab, return reactive state.

## 1.7 Vue — Service Layer (Axios per Modul)

Pisahkan HTTP call ke `services/` — composable **tidak boleh** tahu tentang axios secara langsung.

**Struktur folder**
```
src/
  services/
    api.js              ← axios instance
    authService.js       ← /auth endpoints
    campaignService.js
    backingService.js
    notifService.js
  composables/
    useCampaign.js       ← pakai service
    useAuth.js
  stores/
    useCampaignStore.js
```

**Alur tanggung jawab**
```
Component → Composable → Service → api.js → Laravel API
```

**`api.js` — instance tunggal**
```js
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

export default api
```

**`campaignService.js` — endpoint per modul**
```js
import api from './api'

export const campaignService = {
  getAll: (params) => api.get('/campaigns', { params }),
  getOne: (id) => api.get(`/campaigns/${id}`),
  store: (data) => api.post('/campaigns', data),
  update: (id, d) => api.patch(`/campaigns/${id}`, d),
  back: (id, d) => api.post(`/campaigns/${id}/back`, d),
}
```

**Penggunaan di composable**
```js
import { campaignService } from '@/services/campaignService'

const res = await campaignService.getOne(id) // composable tidak import axios
```

## 1.8 Vue — Library Stack Wajib

Library yang wajib dipakai di project ini — konsisten di semua modul.

| Library | Versi | Keterangan |
|---|---|---|
| Vue Router | v4 | Routing & navigation. Gunakan Nested Routes untuk layout. |
| Pinia | v2 | Global state management. Satu store per domain entity. |
| Axios | v1 | HTTP client. Bungkus di `services/api.js` — jangan import langsung di composable. |
| Tailwind CSS | v3 | Utility-first styling. Jangan nulis custom CSS kecuali terpaksa. |
| PrimeVue | v4 | UI component library. Gunakan preset Aura/Lara. Includes Button, DataTable, Dialog, Calendar, dll. |
| PrimeIcons | bundled | 270+ icon via class `pi pi-check`. Sudah bundled bersama PrimeVue, tidak perlu install terpisah. |
| Vee-Validate + Yup | v4 | Form validation. Definisikan schema Yup — jangan validasi manual di event handler. |
| Vue Toastification | v2 | Notifikasi toast. Jangan pakai `alert()` atau `console.log` untuk user feedback. |
| Day.js | v1 | Date formatting. Gunakan untuk format tanggal deadline, sisa hari, dll. |

---

# Section 2 — Timeline Pelatihan

## 2.1 Overview Timeline 1 Minggu

> ⚠️ Timeline ini bersifat **rekomendasi** — tidak ada kewajiban selesai semua dalam 1 hari. Selesaikan semampumu, utamakan pemahaman daripada mengejar semua fitur.

| Hari | Backend Track | Frontend Track |
|---|---|---|
| H-1 | Auth & Setup | Setup & Auth Pages |
| H-2 | Campaign CRUD | Campaign List & Detail |
| H-3 | Tier & Backing | Create Form + Tier |
| H-4 | Escrow & Transaksi | Backing Flow |
| H-5 | Jobs & Queue | Dashboard Creator & Backer |
| H-6 | Notifikasi & Dashboard | Notifikasi & Admin |
| H-7 | Admin & Polish | Responsive & Polish |

## 2.2 Backend — Target per Hari (Rekomendasi)

> Ini panduan — selesaikan semampumu, konsultasikan dengan mentor jika stuck.

| Hari | Target | Detail Rekomendasi |
|---|---|---|
| H-1 | Setup & Auth | Instalasi Laravel, `.env` & DB config. Migrasi `users`. Register, Login, Logout, Email Verification (Sanctum). |
| H-2 | Campaign CRUD | Migration `campaigns` + `categories`. Controller. Endpoint: index, show, store, update. Validasi boleh di controller. |
| H-3 | Tier & Backing | Migration `tiers` & `backings`. Tier CRUD. Endpoint backing store + escrow logic (`collected_amount` bertambah). |
| H-4 | Escrow & Transaksi | Migration `transactions`. Payment mock → disbursement logic, refund logic. Virtual balance user. |
| H-5 | Jobs & Scheduler | `CheckExpiredCampaigns` command. `DisburseCampaignJob` + `RefundBackersJob` via queue. |
| H-6 | Notifikasi & Dashboard | `Notifications` table. In-app notif + Mail. Dashboard API creator & backer. Saldo endpoint. |
| H-7 | Admin & Polish | Admin endpoints (approval, user list, force-fail). Testing endpoint + dokumentasi Postman. |

## 2.3 Frontend — Target per Hari (Rekomendasi)

> Ini panduan — selesaikan semampumu, konsultasikan dengan mentor jika stuck.

| Hari | Target | Detail Rekomendasi |
|---|---|---|
| H-1 | Setup & Auth | Buat project Vite + Vue 3. Install Pinia, Vue Router, Tailwind, Axios, PrimeVue. Halaman Login & Register. |
| H-2 | Campaign Pages | Halaman list kampanye (grid card + filter). Halaman detail: info, progress bar, daftar tier, daftar backer. |
| H-3 | Create & Tier UI | Halaman buat kampanye (form step). UI management tier (add/edit/delete). Preview sebelum submit. |
| H-4 | Backing Flow | Pilih tier / nominal bebas. Payment confirmation dialog. Riwayat backing backer. Update `remaining_quota`. |
| H-5 | Dashboard | Dashboard creator: stats card, grafik funding. Dashboard backer: list kontribusi & saldo. |
| H-6 | Notifikasi & Admin | Bell icon + badge + dropdown notifikasi. Halaman admin approval queue + user list. |
| H-7 | Polish & Responsive | Responsive mobile. Loading skeleton, error state, empty state. Final QA seluruh halaman. |

## 2.4 FE Only — Mock JSON API

Untuk peserta yang hanya mengerjakan Frontend — gunakan salah satu opsi ini sebagai pengganti Laravel backend.

### Opsi 1 (Rekomendasi) — json-server (local, CoFund-shaped)

```json
{
  "campaigns": [
    {
      "id": 1,
      "title": "EcoBag — Tas Daur Ulang",
      "target_amount": 5000000,
      "collected_amount": 3200000,
      "deadline": "2026-07-30",
      "status": "active",
      "category_id": 2,
      "creator_id": 1
    }
  ],
  "backings": [
    { "id": 1, "campaign_id": 1, "user_id": 2, "amount": 100000, "status": "completed" }
  ],
  "users": [
    { "id": 1, "name": "Alice", "email": "alice@mail.com", "role": "creator" }
  ]
}
```

**Setup & run**
```bash
# Install sekali
npm install -g json-server

# Jalankan (port 3001)
npx json-server db.json --port 3001
```

Endpoints otomatis tersedia:
- `GET /campaigns` → list semua
- `GET /campaigns/1` → detail
- `POST /campaigns` → tambah baru
- `PATCH /campaigns/1` → update
- `GET /backings?campaign_id=1`

### Opsi 2 — DummyJSON (online, no setup)

- `https://dummyjson.com/products` → mock campaigns
- `https://dummyjson.com/users` → mock users
- Beda struktur, perlu adapter di service

**Konfigurasi base URL (kedua opsi)**
```
VITE_API_URL=http://localhost:3001
```
```js
axios.create({ baseURL: import.meta.env.VITE_API_URL })
```

## 2.5 Checklist Akhir Pelatihan

> Semua item ini adalah target ideal di hari ke-7 — sesuaikan dengan progress masing-masing.

**Backend Checklist**
- [ ] Auth: register, login, verify email
- [ ] Campaign CRUD + status flow lengkap
- [ ] Tier management + quota tracking
- [ ] Backing + escrow (`collected_amount`)
- [ ] Transaction: payment, refund, disbursement
- [ ] Scheduled job: check-expired berjalan
- [ ] Queue job: RefundBackers + Disburse
- [ ] Notification: in-app + email terkirim
- [ ] Dashboard API creator & backer
- [ ] Admin: approval queue + user management
- [ ] Semua endpoint terdokumentasi di Postman

**Frontend Checklist**
- [ ] Auth pages: login, register, verify
- [ ] Campaign list + filter + search
- [ ] Campaign detail: progress bar + tiers
- [ ] Campaign create form (multi-step)
- [ ] Tier management UI
- [ ] Backing flow + payment mock dialog
- [ ] Dashboard creator (grafik + stats)
- [ ] Dashboard backer (riwayat + saldo)
- [ ] Notification bell + dropdown
- [ ] Admin panel (approval + user list)
- [ ] Responsive mobile + error/empty states

---

## Penutup

**Selamat Berlatih!**
Ikuti standar, kerjakan semampumu, tanya jika buntu.

- **Standarisasi dulu** sebelum nulis kode
- **Selesaikan semampumu**, utamakan pemahaman
- **Tanya mentor** jika stuck

*CoFund Pelatihan WebDev · v1.0 · Juli 2026*
