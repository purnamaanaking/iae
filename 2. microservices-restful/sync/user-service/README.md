
# User Service

Service untuk manajemen data pengguna dalam arsitektur microservices dengan komunikasi **sinkron**. Dibangun menggunakan Laravel 10 dan dijalankan langsung di mesin lokal tanpa Docker. Service ini berdiri sendiri dan tidak melakukan panggilan ke service lain — hanya menyediakan data pengguna yang dikonsumsi oleh Order Service.

## Daftar Isi

- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Struktur Database](#struktur-database)
- [Endpoint API](#endpoint-api)
- [Format Response](#format-response)
- [Cara Menjalankan](#cara-menjalankan)

---

## Teknologi yang Digunakan

| Teknologi | Fungsi |
|-----------|--------|
| **Laravel 10** | Framework utama |
| **PHP 8.1+** | Runtime PHP |
| **MySQL** | Database lokal |
| **Laravel Sanctum** | API token authentication |

**Dependency:**

| Package | Fungsi |
|---------|--------|
| `laravel/framework ^10.0` | Core Laravel framework |
| `laravel/sanctum ^3.2` | API token authentication |
| `guzzlehttp/guzzle ^7.2` | HTTP client |

> Service ini **tidak melakukan panggilan ke service lain**. Hanya menyediakan endpoint yang dikonsumsi oleh Order Service saat mengambil detail pengguna.

---

## Konfigurasi Environment

| Variabel | Nilai | Keterangan |
|----------|-------|------------|
| `APP_URL` | `http://localhost` | URL aplikasi |
| `DB_HOST` | `127.0.0.1` | Host database lokal |
| `DB_PORT` | `3306` | Port MySQL |
| `DB_DATABASE` | `user-service` | Nama database |
| `DB_USERNAME` | `root` | Username database |
| `DB_PASSWORD` | `root` | Password database |
| `QUEUE_CONNECTION` | `sync` | Tidak menggunakan antrian |

---

## Struktur Database

### Tabel `users`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID, otomatis digenerate) |
| `name` | VARCHAR(255) | Nama pengguna |
| `email` | VARCHAR(255) | Email unik |
| `email_verified_at` | TIMESTAMP | Waktu verifikasi email (nullable) |
| `password` | VARCHAR(255) | Password (di-hash dengan bcrypt, disembunyikan dari response) |
| `remember_token` | VARCHAR(100) | Token remember me (disembunyikan dari response) |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

### Tabel `personal_access_tokens` (Laravel Sanctum)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT | Primary key |
| `tokenable_type` | VARCHAR | Tipe model pemilik token |
| `tokenable_id` | BIGINT | ID pemilik token |
| `name` | VARCHAR | Nama token |
| `token` | VARCHAR(64) | Token unik |
| `abilities` | TEXT | Hak akses token (nullable) |
| `last_used_at` | TIMESTAMP | Terakhir digunakan (nullable) |
| `expires_at` | TIMESTAMP | Waktu kedaluwarsa (nullable) |

### Data Awal (Seeder)

Saat migrasi dijalankan dengan `--seed`, akan dibuat data pengguna awal sebagai berikut:

| Jenis | Jumlah | Keterangan |
|-------|--------|------------|
| Pengguna acak | 10 | Digenerate menggunakan factory dengan data acak |
| Pengguna khusus | 1 | Nama: `Purnama Anaking`, Email: `purnama.anaking@gmail.com` |

> Password default untuk semua pengguna seeder adalah `password`.

---

## Endpoint API

**Base URL:** `http://127.0.0.1:8000/api`

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/users` | Ambil semua pengguna | Tidak |
| POST | `/users` | Buat pengguna baru | Tidak |
| GET | `/users/{id}` | Ambil pengguna berdasarkan ID | Tidak |
| PUT | `/users/{id}` | Perbarui data pengguna | Tidak |
| DELETE | `/users/{id}` | Hapus pengguna | Tidak |
| GET | `/user` | Ambil data pengguna yang sedang login | Ya (Sanctum) |

---

### GET /users

Mengambil semua data pengguna.

```bash
curl -X GET http://127.0.0.1:8000/api/users
```

**Response:**
```json
{
  "status": "Success",
  "message": "List of Users",
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": "2025-04-27T10:00:00.000000Z",
      "created_at": "2025-04-27T10:00:00.000000Z",
      "updated_at": "2025-04-27T10:00:00.000000Z"
    }
  ]
}
```

---

### POST /users

Membuat pengguna baru. Password akan di-hash otomatis menggunakan bcrypt.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `name` | string | Ya | Nama pengguna |
| `email` | string | Ya | Email pengguna |
| `password` | string | Ya | Password (akan di-hash otomatis) |

```bash
curl -X POST http://127.0.0.1:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "User created successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2025-04-27T10:00:00.000000Z",
    "created_at": "2025-04-27T10:00:00.000000Z",
    "updated_at": "2025-04-27T10:00:00.000000Z"
  }
}
```

**Response Gagal (Validasi):**
```json
{
  "status": "Failed",
  "message": {
    "name": ["The name field is required."],
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  },
  "data": null
}
```

---

### GET /users/{id}

Mengambil data pengguna berdasarkan UUID.

```bash
curl -X GET http://127.0.0.1:8000/api/users/550e8400-e29b-41d4-a716-446655440000
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "User found",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2025-04-27T10:00:00.000000Z",
    "created_at": "2025-04-27T10:00:00.000000Z",
    "updated_at": "2025-04-27T10:00:00.000000Z"
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "User not found",
  "data": null
}
```

---

### PUT /users/{id}

Memperbarui data pengguna berdasarkan UUID. Hanya `name` dan `password` yang dapat diperbarui.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `name` | string | Tidak | Nama baru pengguna |
| `password` | string | Tidak | Password baru (akan di-hash otomatis) |

```bash
curl -X PUT http://127.0.0.1:8000/api/users/550e8400-e29b-41d4-a716-446655440000 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Updated",
    "password": "newpassword123"
  }'
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "User updated successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Updated",
    "email": "john@example.com",
    "email_verified_at": "2025-04-27T10:00:00.000000Z",
    "created_at": "2025-04-27T10:00:00.000000Z",
    "updated_at": "2025-04-27T10:30:00.000000Z"
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "User not found",
  "data": null
}
```

---

### DELETE /users/{id}

Menghapus pengguna berdasarkan UUID.

```bash
curl -X DELETE http://127.0.0.1:8000/api/users/550e8400-e29b-41d4-a716-446655440000
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "User deleted successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "User not found",
  "data": null
}
```

---

## Format Response

Semua endpoint menggunakan format response yang konsisten:

```json
{
  "status": "Success | Failed",
  "message": "Pesan deskriptif",
  "data": {} | [] | null
}
```

> `password` dan `remember_token` selalu disembunyikan dari semua response API.

---

## Cara Menjalankan

Service ini dijalankan langsung menggunakan `php artisan serve` tanpa Docker.

### Langkah-langkah

**1. Install dependency**

```bash
composer install
```

**2. Jalankan migrasi dan seeder**

```bash
php artisan migrate:refresh --seed
```

**3. Jalankan server**

```bash
php artisan serve --port=8000
```

Service akan berjalan di `http://127.0.0.1:8000` dan siap dikonsumsi oleh Order Service maupun klien langsung.
