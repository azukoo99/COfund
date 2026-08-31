# Modul 1: Autentikasi & Manajemen Akun (`auth.md`)

Dokumentasi komprehensif untuk subsistem Autentikasi, Verifikasi Email, Manajemen Profil, Upgrade Role, serta Alur Lupa & Reset Kata Sandi pada platform crowdfunding CoFund.

---

## 1. Judul & Deskripsi Singkat
Modul **Autentikasi & Akun** menangani siklus hidup (*lifecycle*) pengguna di platform CoFund. Modul ini menyediakan registrasi akun baru, verifikasi email berbasis tanda tangan digital (*signed URL*), penerbitan token berbasis Laravel Sanctum, otorisasi berbasis peran (*RBAC*), peningkatan hak akses (*upgrade to creator*), dan pemulihan kata sandi (*forgot & reset password*).

---

## 2. Arsitektur Modul

### Tabel Komponen Laravel
| Komponen | Nama File / Kelas | Tanggung Jawab |
| :--- | :--- | :--- |
| **Controllers** | `App\Http\Controllers\Auth\RegisterController`<br>`App\Http\Controllers\Auth\VerifyEmailController`<br>`App\Http\Controllers\Auth\LoginController`<br>`App\Http\Controllers\Auth\PasswordResetController` | Menangani HTTP request autentikasi, menginisiasi token Sanctum, memvalidasi input, dan mengembalikan response JSON standar. |
| **Models** | `App\Models\User` | Representasi entitas pengguna di database (`users`), hashing password `bcrypt`, relasi ke campaigns, backings, dan notifications. |
| **Requests** | `App\Http\Requests\Auth\RegisterRequest`<br>`App\Http\Requests\Auth\LoginRequest`<br>`App\Http\Requests\Auth\ResetPasswordRequest` | Validasi payload form request dengan pesan error terstruktur. |
| **Events & Notifications** | `Illuminate\Auth\Events\Registered`<br>`Illuminate\Auth\Notifications\VerifyEmail`<br>`Illuminate\Auth\Notifications\ResetPassword` | Mengirim email verifikasi dan link token pemulihan kata sandi. |
| **Middleware** | `auth:sanctum`<br>`verified`<br>`throttle:api` | Memastikan request terautentikasi bearer token, email terverifikasi, dan mencegah serangan *brute-force*. |

### Alur Kerja (Workflow)

```text
[REGISTRASI & LOGIN FLOW]
User (Client)
   │
   ├───> POST /api/register
   │        │
   │        ├── Validasi Input (Email Unik, Password Min 8)
   │        ├── Hash Password (Bcrypt) -> Buat User (role: backer)
   │        ├── Trigger Event(Registered) -> Kirim Email Verifikasi
   │        └── Return HTTP 201 (User Object + PlainText Token)
   │
   ├───> Klik Link Email Verifikasi (GET /api/email/verify/{id}/{hash})
   │        │
   │        ├── Validasi Signature Hash & Expiry
   │        ├── Set user.email_verified_at = now()
   │        └── Return HTTP 200 (Email verified successfully)
   │
   └───> POST /api/login
            │
            ├── Cek User & Password Hash (Hash::check)
            ├── Cek Status Suspended (user.is_suspended == true -> 403)
            ├── Buat Token Baru (Sanctum createToken)
            └── Return HTTP 200 (Token + User Profile + Role)
```

---

## 3. Struktur File
```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Auth/
│   │   │       ├── LoginController.php
│   │   │       ├── PasswordResetController.php
│   │   │       ├── RegisterController.php
│   │   │       └── VerifyEmailController.php
│   │   └── Requests/
│   │       └── Auth/
│   │           ├── LoginRequest.php
│   │           ├── RegisterRequest.php
│   │           └── ResetPasswordRequest.php
│   └── Models/
│       └── User.php
└── routes/
    └── api.php
```

---

## 4. API Endpoints

### 4.1. Registrasi Akun Baru
- **HTTP Method:** `POST`
- **Path:** `/api/register`
- **Autentikasi / Middleware:** `guest`, `throttle:6,1`
- **Deskripsi:** Mendaftarkan pengguna baru ke platform dengan role bawaan `backer`.

#### Tabel Parameter
| Parameter | Posisi | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `name` | Body (JSON) | `string` | Wajib | `required\|string\|max:255` | Nama lengkap pengguna |
| `email` | Body (JSON) | `string` | Wajib | `required\|string\|email\|max:255\|unique:users` | Alamat email unik |
| `password` | Body (JSON) | `string` | Wajib | `required\|string\|min:8\|confirmed` | Kata sandi minimal 8 karakter |
| `password_confirmation` | Body (JSON) | `string` | Wajib | `required\|string\|min:8` | Konfirmasi kecocokan kata sandi |

#### Request Payload (JSON)
```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Response Sukses (HTTP 201 Created)
```json
{
  "message": "Registrasi berhasil. Silakan periksa email Anda untuk verifikasi.",
  "user": {
    "id": 15,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "role": "backer",
    "is_suspended": false,
    "created_at": "2026-08-28T06:00:00.000000Z"
  },
  "token": "1|e4d8f2g9h3j1k5l7m9n2p4q6r8s0t2u4v6w8x0y2"
}
```

#### Efek Samping (Side Effects)
- Record baru tersimpan di tabel `users`.
- Record token tersimpan di tabel `personal_access_tokens`.
- Event `Registered` dipicu dan antrian pengiriman email verifikasi dikirimkan.

#### Tabel Error Handling
| HTTP Code | Body JSON | Kondisi |
| :--- | :--- | :--- |
| `422 Unprocessable Content` | `{"message":"The email has already been taken.","errors":{"email":["The email has already been taken."]}}` | Email sudah terdaftar sebelumnya. |
| `422 Unprocessable Content` | `{"message":"The password confirmation does not match.","errors":{"password":["The password confirmation does not match."]}}` | `password_confirmation` berbeda dengan `password`. |
| `429 Too Many Requests` | `{"message":"Too Many Attempts."}` | Melebihi batas rate limit registrasi (6 request / menit). |

---

### 4.2. Login Pengguna
- **HTTP Method:** `POST`
- **Path:** `/api/login`
- **Autentikasi / Middleware:** `guest`, `throttle:10,1`
- **Deskripsi:** Mengotentikasi kredensial email & password lalu mengembalikan Sanctum Bearer Token.

#### Tabel Parameter
| Parameter | Posisi | Tipe Data | Wajib/Opsional | Aturan Validasi | Deskripsi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `email` | Body (JSON) | `string` | Wajib | `required\|string\|email` | Email terdaftar |
| `password` | Body (JSON) | `string` | Wajib | `required\|string` | Kata sandi akun |

#### Request Payload (JSON)
```json
{
  "email": "budi@example.com",
  "password": "password123"
}
```

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Login berhasil.",
  "user": {
    "id": 15,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "role": "backer",
    "email_verified_at": "2026-08-28T06:05:00.000000Z",
    "is_suspended": false,
    "wallet_balance": "0.00"
  },
  "token": "2|k9l8m7n6p5q4r3s2t1u0v9w8x7y6z5a4b3c2d1e0"
}
```

#### Tabel Error Handling
| HTTP Code | Body JSON | Kondisi |
| :--- | :--- | :--- |
| `401 Unauthorized` | `{"message":"Kredensial yang diberikan tidak cocok dengan data kami."}` | Password salah atau email tidak ditemukan. |
| `403 Forbidden` | `{"message":"Akun Anda telah disuspend oleh administrator."}` | Akun memiliki status `is_suspended = true`. |

---

### 4.3. Mendapatkan Data Profil Saya (Me)
- **HTTP Method:** `GET`
- **Path:** `/api/me`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mengambil informasi profil lengkap, saldo dompet, dan role pengguna yang sedang login.

#### Request Headers
```http
Authorization: Bearer 2|k9l8m7n6p5q4r3s2t1u0v9w8x7y6z5a4b3c2d1e0
Accept: application/json
```

#### Response Sukses (HTTP 200 OK)
```json
{
  "user": {
    "id": 15,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "role": "backer",
    "email_verified_at": "2026-08-28T06:05:00.000000Z",
    "is_suspended": false,
    "created_at": "2026-08-28T06:00:00.000000Z"
  }
}
```

---

### 4.4. Upgrade Role ke Creator
- **HTTP Method:** `POST`
- **Path:** `/api/me/upgrade-creator`
- **Autentikasi / Middleware:** `auth:sanctum`, `verified`
- **Deskripsi:** Mengubah status peran pengguna dari `backer` menjadi `creator` agar dapat menginisiasi kampanye penggalangan dana.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Selamat! Akun Anda berhasil di-upgrade menjadi Creator.",
  "user": {
    "id": 15,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "role": "creator",
    "email_verified_at": "2026-08-28T06:05:00.000000Z"
  }
}
```

---

### 4.5. Logout Pengguna
- **HTTP Method:** `POST`
- **Path:** `/api/logout`
- **Autentikasi / Middleware:** `auth:sanctum`
- **Deskripsi:** Mencabut (*revoke*) token Sanctum yang sedang aktif digunakan.

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Logout berhasil. Token telah dicabut."
}
```

---

### 4.6. Lupa Kata Sandi (Forgot Password)
- **HTTP Method:** `POST`
- **Path:** `/api/forgot-password`
- **Autentikasi / Middleware:** `guest`, `throttle:5,1`
- **Deskripsi:** Mengirimkan token reset kata sandi ke email pengguna yang terdaftar.

#### Request Payload (JSON)
```json
{
  "email": "budi@example.com"
}
```

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Link reset password telah dikirimkan ke email Anda."
}
```

---

### 4.7. Atur Ulang Kata Sandi (Reset Password)
- **HTTP Method:** `POST`
- **Path:** `/api/reset-password`
- **Autentikasi / Middleware:** `guest`
- **Deskripsi:** Mereset kata sandi lama dengan kata sandi baru menggunakan token valid.

#### Request Payload (JSON)
```json
{
  "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
  "email": "budi@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

#### Response Sukses (HTTP 200 OK)
```json
{
  "message": "Password Anda berhasil diatur ulang. Silakan login."
}
```

---

## 5. Skema Sumber Daya (Resource Schema)

### Sample JSON: `User`
```json
{
  "id": 15,
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "role": "creator",
  "email_verified_at": "2026-08-28T06:05:00.000000Z",
  "is_suspended": false,
  "created_at": "2026-08-28T06:00:00.000000Z",
  "updated_at": "2026-08-28T06:10:00.000000Z"
}
```

### Tabel Definisi Kolom Data
| Kolom | Tipe Database | Nullable | Deskripsi |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary key unik pengguna |
| `name` | `VARCHAR(255)` | Tidak | Nama lengkap pengguna |
| `email` | `VARCHAR(255)` | Tidak | Alamat surel unik untuk autentikasi |
| `role` | `ENUM('backer','creator','admin')` | Tidak | Peran otorisasi sistem (default: `backer`) |
| `email_verified_at` | `TIMESTAMP` | Ya | Waktu pengguna mengonfirmasi email |
| `password` | `VARCHAR(255)` | Tidak | Hash Bcrypt kata sandi akun |
| `is_suspended` | `BOOLEAN` | Tidak | Flag pemblokiran akses akun oleh Admin |
| `created_at` | `TIMESTAMP` | Ya | Waktu registrasi akun |
| `updated_at` | `TIMESTAMP` | Ya | Waktu terakhir data akun dimutakhirkan |

---

## 6. Pengujian Postman (Postman Testing Script)

```javascript
// Test Script: POST /api/login
pm.test("Status code is 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Response contains Sanctum Bearer Token", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('token');
    pm.expect(jsonData.token).to.be.a('string');
    
    // Set token ke environment collection
    pm.environment.set("auth_token", jsonData.token);
    pm.environment.set("current_user_id", jsonData.user.id);
    pm.environment.set("current_user_role", jsonData.user.role);
});

pm.test("User data is not suspended", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.user.is_suspended).to.be.false;
});
```

---

## 7. Kasus Pengujian (Test Cases Table)

| No | Skenario Uji | Input Payload | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **TC-AUTH-01** | Registrasi berhasil dengan kredensial valid | `{"name":"User Test", "email":"valid@mail.com", "password":"password123", "password_confirmation":"password123"}` | `201 Created`, token diterbitkan, user tersimpan di DB. |
| **TC-AUTH-02** | Registrasi gagal karena email duplikat | `{"name":"User Duplikat", "email":"valid@mail.com", ...}` | `422 Unprocessable Content`, error validasi email unique. |
| **TC-AUTH-03** | Login sukses akun valid | `{"email":"backer@example.com", "password":"password"}` | `200 OK`, token diterima, user object valid. |
| **TC-AUTH-04** | Login gagal password salah | `{"email":"backer@example.com", "password":"wrong_password"}` | `401 Unauthorized`, pesan error kredensial tidak cocok. |
| **TC-AUTH-05** | Login gagal akun ter-suspend | `{"email":"suspended@example.com", "password":"password"}` | `403 Forbidden`, pesan akun disuspend admin. |
| **TC-AUTH-06** | Upgrade role ke creator | Token `backer` valid | `200 OK`, user.role berubah menjadi `creator`. |
| **TC-AUTH-07** | Akses endpoint `/api/me` tanpa token | Header tanpa `Authorization` | `401 Unauthorized`, Unauthenticated. |

---

## 8. Pemecahan Masalah (Troubleshooting & Known Issues)

1. **Error: `401 Unauthenticated` saat mengakses endpoint terproteksi**
   - *Penyebab:* Header `Authorization` tidak menyertakan prefix `Bearer ` atau token kedaluwarsa/salah.
   - *Solusi:* Pastikan format header adalah `Authorization: Bearer <token_kamu>`.

2. **Email Verifikasi tidak terkirim di lingkungan lokal**
   - *Penyebab:* Konfigurasi `MAIL_MAILER=smtp` tidak aktif atau `QUEUE_CONNECTION=sync` belum disesuaikan.
   - *Solusi:* Gunakan Mailpit/Mailtrap di `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`).

3. **Status `403 Your email address is not verified`**
   - *Penyebab:* Akun mencoba membuat kampanye atau backing namun kolom `email_verified_at` bernilai `NULL`.
   - *Solusi:* Jalankan verifikasi email atau update manual via seeder/tinker: `User::where('email', '...')->update(['email_verified_at' => now()]);`.

---

## 9. Matriks RBAC (Role-Based Access Control)

| Endpoint | Publik (Guest) | Backer (Terverifikasi) | Creator (Terverifikasi) | Admin |
| :--- | :---: | :---: | :---: | :---: |
| `POST /api/register` | ✅ | ❌ | ❌ | ❌ |
| `POST /api/login` | ✅ | ❌ | ❌ | ❌ |
| `GET /api/me` | ❌ | ✅ | ✅ | ✅ |
| `POST /api/me/upgrade-creator` | ❌ | ✅ | ✅ | ❌ |
| `POST /api/logout` | ❌ | ✅ | ✅ | ✅ |
| `POST /api/forgot-password` | ✅ | ✅ | ✅ | ✅ |
| `POST /api/reset-password` | ✅ | ✅ | ✅ | ✅ |
