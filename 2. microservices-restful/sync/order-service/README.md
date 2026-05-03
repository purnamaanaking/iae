
# Order Service

Service untuk manajemen data pesanan dalam arsitektur microservices dengan komunikasi **sinkron**. Dibangun menggunakan Laravel 10 dan dijalankan langsung di mesin lokal tanpa Docker. Service ini berperan sebagai **orchestrator** — menerima permintaan pembuatan pesanan, menyimpannya ke database, lalu memanggil Product Service secara langsung melalui HTTP untuk memperbarui stok.

## Daftar Isi

- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Struktur Database](#struktur-database)
- [Komunikasi Antar Service](#komunikasi-antar-service)
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
| **Guzzle HTTP** | HTTP client untuk komunikasi ke service lain |
| **Laravel Sanctum** | API token authentication |

**Dependency:**

| Package | Fungsi |
|---------|--------|
| `laravel/framework ^10.0` | Core Laravel framework |
| `laravel/sanctum ^3.2` | API token authentication |
| `guzzlehttp/guzzle ^7.2` | HTTP client untuk memanggil User Service dan Product Service |

> Service ini **tidak menggunakan RabbitMQ**. Semua komunikasi dilakukan secara sinkron (blocking) melalui HTTP langsung ke service lain.

---

## Konfigurasi Environment

| Variabel | Nilai | Keterangan |
|----------|-------|------------|
| `APP_URL` | `http://localhost` | URL aplikasi |
| `DB_HOST` | `127.0.0.1` | Host database lokal |
| `DB_PORT` | `3306` | Port MySQL |
| `DB_DATABASE` | `order-service` | Nama database |
| `DB_USERNAME` | `root` | Username database |
| `DB_PASSWORD` | `root` | Password database |
| `QUEUE_CONNECTION` | `sync` | Tidak menggunakan antrian |

---

## Struktur Database

### Tabel `orders`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID, otomatis digenerate) |
| `code` | VARCHAR(255) | Kode pesanan unik (diisi manual saat request) |
| `product_id` | VARCHAR(255) | UUID produk dari Product Service |
| `user_id` | VARCHAR(255) | UUID pengguna dari User Service |
| `status` | VARCHAR(255) | Status pesanan (default: `pending`) |
| `total_price` | DECIMAL(10,2) | Total harga pesanan |
| `quantity` | INT | Jumlah barang dipesan (default: 1) |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

> `product_id` dan `user_id` disimpan sebagai string tanpa foreign key constraint di database, karena data aslinya berada di service yang berbeda.

> Berbeda dengan versi async, field `code` pada service ini **wajib diisi** di request body dan tidak digenerate otomatis oleh sistem.

---

## Komunikasi Antar Service

Semua komunikasi dilakukan secara **sinkron** (blocking) menggunakan Guzzle HTTP ke alamat `127.0.0.1` (localhost).

### Saat Membuat Pesanan — `store()`

Order Service langsung memanggil Product Service dan **menunggu** hingga stok berhasil diperbarui sebelum mengembalikan respons.

```
Klien → POST /api/orders (Order Service: 8002)
              ↓
      Validasi data pesanan
              ↓
      Simpan pesanan ke DB
              ↓
  HTTP POST → http://127.0.0.1:8001/api/products/{product_id}/update-stock ← BLOCKING!
              ↓ (menunggu respons dari Product Service)
      Kembalikan respons ke klien
```

### Saat Mengambil Detail Pesanan — `show()`

```
Klien → GET /api/orders/{id} (Order Service: 8002)
              ↓
      Ambil data pesanan dari DB
              ↓
  HTTP GET → http://127.0.0.1:8001/api/products/{product_id} ← BLOCKING!
              ↓
  HTTP GET → http://127.0.0.1:8000/api/users/{user_id}       ← BLOCKING!
              ↓
      Gabungkan semua data → Kembalikan ke klien
```

| Tujuan | URL | Kapan Dipanggil |
|--------|-----|-----------------|
| Product Service | `http://127.0.0.1:8001` | Update stok & ambil detail produk |
| User Service | `http://127.0.0.1:8000` | Ambil detail pengguna |

---

## Endpoint API

**Base URL:** `http://127.0.0.1:8002/api`

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/orders` | Ambil semua pesanan | Tidak |
| POST | `/orders` | Buat pesanan baru (trigger HTTP ke Product Service) | Tidak |
| GET | `/orders/{id}` | Ambil pesanan beserta detail produk & pengguna | Tidak |
| GET | `/orders/user/{uuid}` | Ambil semua pesanan milik pengguna tertentu | Tidak |
| GET | `/user` | Ambil data pengguna yang sedang login | Ya (Sanctum) |

---

### GET /orders

Mengambil semua data pesanan.

```bash
curl -X GET http://127.0.0.1:8002/api/orders
```

**Response:**
```json
{
  "status": "Success",
  "message": "List of orders",
  "data": [
    {
      "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "code": "OR-001",
      "product_id": "550e8400-e29b-41d4-a716-446655440000",
      "user_id": "660e8400-f39b-41d4-b827-557766551111",
      "status": "pending",
      "total_price": "3000.00",
      "quantity": 2,
      "created_at": "2025-04-27T10:50:00.000000Z",
      "updated_at": "2025-04-27T10:50:00.000000Z"
    }
  ]
}
```

---

### POST /orders

Membuat pesanan baru sekaligus memanggil Product Service secara sinkron untuk memperbarui stok produk.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `code` | string | Ya | Kode pesanan unik (diisi manual, tidak digenerate otomatis) |
| `product_id` | string | Ya | UUID produk dari Product Service |
| `user_id` | string | Ya | UUID pengguna dari User Service |
| `status` | string | Ya | Status pesanan (contoh: `pending`) |
| `total_price` | number | Ya | Total harga pesanan |
| `quantity` | integer | Ya | Jumlah barang yang dipesan |

```bash
curl -X POST http://127.0.0.1:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "code": "OR-001",
    "product_id": "550e8400-e29b-41d4-a716-446655440000",
    "user_id": "660e8400-f39b-41d4-b827-557766551111",
    "status": "pending",
    "total_price": 3000.00,
    "quantity": 2
  }'
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Order created successfully",
  "data": {
    "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "code": "OR-001",
    "product_id": "550e8400-e29b-41d4-a716-446655440000",
    "user_id": "660e8400-f39b-41d4-b827-557766551111",
    "status": "pending",
    "total_price": "3000.00",
    "quantity": 2,
    "created_at": "2025-04-27T10:50:00.000000Z",
    "updated_at": "2025-04-27T10:50:00.000000Z"
  }
}
```

**Response Gagal (Validasi):**
```json
{
  "status": "Failed",
  "message": {
    "code": ["The code field is required."],
    "product_id": ["The product id field is required."],
    "user_id": ["The user id field is required."],
    "status": ["The status field is required."],
    "total_price": ["The total price field is required."],
    "quantity": ["The quantity field is required."]
  },
  "data": null
}
```

---

### GET /orders/{id}

Mengambil detail pesanan berdasarkan UUID, diperkaya dengan data produk dari **Product Service** dan data pengguna dari **User Service** melalui HTTP call sinkron.

```bash
curl -X GET http://127.0.0.1:8002/api/orders/a1b2c3d4-e5f6-7890-abcd-ef1234567890
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Order found",
  "data": {
    "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "code": "OR-001",
    "product_id": "550e8400-e29b-41d4-a716-446655440000",
    "user_id": "660e8400-f39b-41d4-b827-557766551111",
    "status": "pending",
    "total_price": "3000.00",
    "quantity": 2,
    "created_at": "2025-04-27T10:50:00.000000Z",
    "updated_at": "2025-04-27T10:50:00.000000Z",
    "product": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "code": "PROD-001",
      "name": "Laptop",
      "description": "High performance laptop",
      "price": "1500.00",
      "stock": 8
    },
    "user": {
      "id": "660e8400-f39b-41d4-b827-557766551111",
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "Order not found",
  "data": null
}
```

---

### GET /orders/user/{uuid}

Mengambil semua pesanan milik pengguna tertentu berdasarkan UUID pengguna. Setiap pesanan diperkaya dengan data produk dan pengguna dari service lain secara sinkron.

```bash
curl -X GET http://127.0.0.1:8002/api/orders/user/660e8400-f39b-41d4-b827-557766551111
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Order found",
  "data": [
    {
      "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "code": "OR-001",
      "status": "pending",
      "total_price": "3000.00",
      "quantity": 2,
      "product": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "code": "PROD-001",
        "name": "Laptop",
        "price": "1500.00",
        "stock": 8
      },
      "user": {
        "id": "660e8400-f39b-41d4-b827-557766551111",
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  ]
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

Service ini dijalankan langsung menggunakan `php artisan serve` tanpa Docker.

> Pastikan **User Service** (port 8000) dan **Product Service** (port 8001) sudah berjalan terlebih dahulu sebelum menjalankan Order Service, karena Order Service akan langsung memanggil kedua service tersebut saat ada request masuk.

### Langkah-langkah

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
php artisan serve --port=8002
```

### Simulasi Komunikasi Sinkron

Setelah ketiga service berjalan, buat pesanan dan amati bahwa respons baru diberikan setelah Product Service selesai memperbarui stok (proses blocking):

```bash
curl -X POST http://127.0.0.1:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "code": "OR-001",
    "product_id": "uuid-produk-disini",
    "user_id": "uuid-user-disini",
    "status": "pending",
    "total_price": 1500.00,
    "quantity": 1
  }'
```

Bandingkan kecepatan respons ini dengan versi asinkron di folder `async-docker/` untuk memahami perbedaannya secara nyata.
