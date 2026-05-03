
# User Service

Service untuk manajemen data pengguna dalam arsitektur microservices. Dibangun menggunakan Laravel 10 dan berjalan di dalam container Docker.

## Daftar Isi

- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Struktur Container](#struktur-container)
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
| **PHP 8.2-FPM** | Runtime PHP |
| **MySQL 8** | Database |
| **Nginx** | Web server / reverse proxy |
| **Laravel Sanctum** | API token authentication |
| **Docker** | Kontainerisasi service |

---

## Struktur Container

Service ini terdiri dari 3 container Docker:

| Nama Container | Image | Port | Fungsi |
|----------------|-------|------|--------|
| `user-service-app` | PHP 8.2-FPM (custom) | Internal | Menjalankan aplikasi Laravel |
| `user-service-nginx` | `nginx:stable-alpine` | `8000:80` | Web server / reverse proxy |
| `user-service-db` | `mysql:8` | `3307:3306` | Database MySQL |

Semua container terhubung melalui network `laravel-net`.

---

## Konfigurasi Environment

| Variabel | Nilai | Keterangan |
|----------|-------|------------|
| `APP_NAME` | `UserService` | Nama aplikasi |
| `APP_URL` | `http://localhost:8000` | URL aplikasi |
| `DB_HOST` | `user_db` | Hostname database (nama service Docker) |
| `DB_PORT` | `3306` | Port database |
| `DB_DATABASE` | `user_service` | Nama database |
| `DB_USERNAME` | `root` | Username database |
| `DB_PASSWORD` | `secret` | Password database |
| `QUEUE_CONNECTION` | `sync` | Tidak menggunakan antrian (sinkron) |

---

## Struktur Database

### Tabel `users`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID, otomatis digenerate) |
| `name` | VARCHAR(255) | Nama pengguna |
| `email` | VARCHAR(255) | Email unik |
| `email_verified_at` | TIMESTAMP | Waktu verifikasi email (nullable) |
| `password` | VARCHAR(255) | Password (di-hash dengan bcrypt) |
| `remember_token` | VARCHAR(100) | Token remember me (nullable) |
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

---

## Endpoint API

**Base URL:** `http://localhost:8000/api`

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
curl -X GET http://localhost:8000/api/users
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

Membuat pengguna baru.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `name` | string | Ya | Nama pengguna |
| `email` | string | Ya | Email pengguna |
| `password` | string | Ya | Password (akan di-hash otomatis) |

```bash
curl -X POST http://localhost:8000/api/users \
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
curl -X GET http://localhost:8000/api/users/550e8400-e29b-41d4-a716-446655440000
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

Memperbarui data pengguna berdasarkan UUID.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `name` | string | Tidak | Nama baru pengguna |
| `password` | string | Tidak | Password baru (akan di-hash otomatis) |

```bash
curl -X PUT http://localhost:8000/api/users/550e8400-e29b-41d4-a716-446655440000 \
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

---

### DELETE /users/{id}

Menghapus pengguna berdasarkan UUID.

```bash
curl -X DELETE http://localhost:8000/api/users/550e8400-e29b-41d4-a716-446655440000
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

---

## Cara Menjalankan

### Menjalankan hanya User Service

```bash
docker-compose up -d --build
```

Kemudian jalankan migrasi dan seeder:

```bash
docker exec -it user-service-app php artisan migrate:refresh --seed
```

### Menjalankan bersama semua service

Gunakan skrip dari folder `async-docker/`:

```bash
./start-all.sh
```

### Menghentikan dan menghapus container

```bash
docker-compose down --rmi all -v
```
