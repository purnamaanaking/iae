
# Student API — RESTful API Monolitik

Contoh implementasi **REST API** sederhana menggunakan arsitektur **monolitik** dengan Laravel 10 dan MySQL. API ini menyediakan operasi CRUD untuk data mahasiswa dan dijalankan langsung di mesin lokal.

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
| **MySQL** | Database |
| **Laravel Sanctum** | API token authentication |

**Dependency:**

| Package | Fungsi |
|---------|--------|
| `laravel/framework ^10.0` | Core Laravel framework |
| `laravel/sanctum ^3.2` | API token authentication |
| `guzzlehttp/guzzle ^7.2` | HTTP client |

---

## Konfigurasi Environment

| Variabel | Nilai | Keterangan |
|----------|-------|------------|
| `APP_URL` | `http://localhost` | URL aplikasi |
| `DB_HOST` | `127.0.0.1` | Host database lokal |
| `DB_PORT` | `3306` | Port MySQL |
| `DB_DATABASE` | `restful-api` | Nama database |
| `DB_USERNAME` | `root` | Username database |
| `DB_PASSWORD` | `root` | Password database |
| `QUEUE_CONNECTION` | `sync` | Tidak menggunakan antrian |

---

## Struktur Database

### Tabel `students`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT | Primary key (auto-increment) |
| `nim` | VARCHAR(255) | Nomor Induk Mahasiswa |
| `name` | VARCHAR(255) | Nama mahasiswa |
| `email` | VARCHAR(255) | Email mahasiswa |
| `address` | VARCHAR(255) | Alamat mahasiswa |
| `phone` | VARCHAR(255) | Nomor telepon mahasiswa |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

---

## Endpoint API

**Base URL:** `http://127.0.0.1:8000/api`

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/students` | Ambil semua data mahasiswa |
| POST | `/students` | Tambah mahasiswa baru |
| GET | `/students/{id}` | Ambil data mahasiswa berdasarkan ID |
| PUT | `/students/{id}` | Perbarui data mahasiswa |
| DELETE | `/students/{id}` | Hapus data mahasiswa |

---

### GET /students

Mengambil semua data mahasiswa.

```bash
curl -X GET http://127.0.0.1:8000/api/students
```

**Response:**
```json
{
  "status": "Success",
  "message": "List of students",
  "data": [
    {
      "id": 1,
      "nim": "12345678",
      "name": "John Doe",
      "email": "john@example.com",
      "address": "Jl. Contoh No. 1, Jakarta",
      "phone": "081234567890",
      "created_at": "2025-03-17T10:00:00.000000Z",
      "updated_at": "2025-03-17T10:00:00.000000Z"
    }
  ]
}
```

---

### POST /students

Menambah data mahasiswa baru.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `nim` | string | Ya | Nomor Induk Mahasiswa |
| `name` | string | Ya | Nama mahasiswa |
| `email` | string | Ya | Email mahasiswa |
| `address` | string | Ya | Alamat mahasiswa |
| `phone` | string | Ya | Nomor telepon mahasiswa |

```bash
curl -X POST http://127.0.0.1:8000/api/students \
  -H "Content-Type: application/json" \
  -d '{
    "nim": "12345678",
    "name": "John Doe",
    "email": "john@example.com",
    "address": "Jl. Contoh No. 1, Jakarta",
    "phone": "081234567890"
  }'
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Student created successfully",
  "data": {
    "id": 1,
    "nim": "12345678",
    "name": "John Doe",
    "email": "john@example.com",
    "address": "Jl. Contoh No. 1, Jakarta",
    "phone": "081234567890",
    "created_at": "2025-03-17T10:00:00.000000Z",
    "updated_at": "2025-03-17T10:00:00.000000Z"
  }
}
```

**Response Gagal (Validasi):**
```json
{
  "status": "Failed",
  "message": {
    "nim": ["The nim field is required."],
    "name": ["The name field is required."],
    "email": ["The email field is required."],
    "address": ["The address field is required."],
    "phone": ["The phone field is required."]
  },
  "data": null
}
```

---

### GET /students/{id}

Mengambil data mahasiswa berdasarkan ID.

```bash
curl -X GET http://127.0.0.1:8000/api/students/1
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Student found",
  "data": {
    "id": 1,
    "nim": "12345678",
    "name": "John Doe",
    "email": "john@example.com",
    "address": "Jl. Contoh No. 1, Jakarta",
    "phone": "081234567890",
    "created_at": "2025-03-17T10:00:00.000000Z",
    "updated_at": "2025-03-17T10:00:00.000000Z"
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "Student not found",
  "data": null
}
```

---

### PUT /students/{id}

Memperbarui data mahasiswa berdasarkan ID.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `nim` | string | Tidak | Nomor Induk Mahasiswa |
| `name` | string | Tidak | Nama mahasiswa |
| `email` | string | Tidak | Email mahasiswa |
| `address` | string | Tidak | Alamat mahasiswa |
| `phone` | string | Tidak | Nomor telepon mahasiswa |

```bash
curl -X PUT http://127.0.0.1:8000/api/students/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Updated",
    "address": "Jl. Baru No. 2, Bandung"
  }'
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Student updated successfully",
  "data": {
    "id": 1,
    "nim": "12345678",
    "name": "John Updated",
    "email": "john@example.com",
    "address": "Jl. Baru No. 2, Bandung",
    "phone": "081234567890",
    "created_at": "2025-03-17T10:00:00.000000Z",
    "updated_at": "2025-03-17T10:30:00.000000Z"
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "Student not found",
  "data": null
}
```

---

### DELETE /students/{id}

Menghapus data mahasiswa berdasarkan ID.

```bash
curl -X DELETE http://127.0.0.1:8000/api/students/1
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Student deleted successfully",
  "data": {
    "id": 1,
    "nim": "12345678",
    "name": "John Doe",
    "email": "john@example.com",
    "address": "Jl. Contoh No. 1, Jakarta",
    "phone": "081234567890",
    "created_at": "2025-03-17T10:00:00.000000Z",
    "updated_at": "2025-03-17T10:00:00.000000Z"
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "Student not found",
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

---

## Cara Menjalankan

**1. Install dependency**

```bash
composer install
```

**2. Jalankan migrasi database**

```bash
php artisan migrate:refresh
```

**3. Jalankan server**

```bash
php artisan serve
```

API akan berjalan di `http://127.0.0.1:8000` dan siap menerima request.
