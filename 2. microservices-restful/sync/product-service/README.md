
# Product Service

Service untuk manajemen data produk dalam arsitektur microservices dengan komunikasi **sinkron**. Dibangun menggunakan Laravel 10 dan dijalankan langsung di mesin lokal tanpa Docker. Service ini menyediakan endpoint untuk CRUD produk serta endpoint khusus pembaruan stok yang dipanggil langsung oleh Order Service melalui HTTP.

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

> Service ini **tidak menggunakan RabbitMQ** dan **tidak menggunakan queue worker**. Pembaruan stok dilakukan secara sinkron — Order Service memanggil endpoint `/update-stock` langsung dan menunggu hasilnya sebelum merespons ke klien.

> Berbeda dengan versi async (`async-docker/product-service`), endpoint `update-stock` di sini **tidak memiliki `sleep(5)`** — stok langsung diperbarui tanpa simulasi delay.

---

## Konfigurasi Environment

| Variabel | Nilai | Keterangan |
|----------|-------|------------|
| `APP_URL` | `http://localhost` | URL aplikasi |
| `DB_HOST` | `127.0.0.1` | Host database lokal |
| `DB_PORT` | `3306` | Port MySQL |
| `DB_DATABASE` | `product-service` | Nama database |
| `DB_USERNAME` | `root` | Username database |
| `DB_PASSWORD` | `root` | Password database |
| `QUEUE_CONNECTION` | `sync` | Tidak menggunakan antrian |

---

## Struktur Database

### Tabel `products`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID, otomatis digenerate) |
| `code` | VARCHAR(255) | Kode produk unik |
| `name` | VARCHAR(255) | Nama produk |
| `description` | VARCHAR(255) | Deskripsi produk (nullable) |
| `price` | DECIMAL(10,2) | Harga produk |
| `stock` | INT | Jumlah stok (default: 0) |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

### Data Awal (Seeder)

Saat migrasi dijalankan dengan `--seed`, akan dibuat **10 produk dummy** secara otomatis menggunakan factory dengan data acak:

| Field | Data yang Digenerate |
|-------|----------------------|
| `code` | Angka acak 8 digit (unik) |
| `name` | Kata acak |
| `description` | Kalimat acak |
| `price` | Angka desimal antara 1–100 |
| `stock` | Angka bulat antara 0–100 |

---

## Endpoint API

**Base URL:** `http://127.0.0.1:8001/api`

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/products` | Ambil semua produk | Tidak |
| POST | `/products` | Buat produk baru | Tidak |
| GET | `/products/{id}` | Ambil produk berdasarkan ID | Tidak |
| PUT | `/products/{id}` | Perbarui data produk | Tidak |
| DELETE | `/products/{id}` | Hapus produk | Tidak |
| POST | `/products/{uuid}/update-stock` | Kurangi stok produk (dipanggil oleh Order Service) | Tidak |
| GET | `/user` | Ambil data pengguna yang sedang login | Ya (Sanctum) |

---

### GET /products

Mengambil semua data produk.

```bash
curl -X GET http://127.0.0.1:8001/api/products
```

**Response:**
```json
{
  "status": "Success",
  "message": "List of products",
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "code": "12345678",
      "name": "Laptop",
      "description": "High performance laptop",
      "price": "1500.00",
      "stock": 10,
      "created_at": "2025-04-27T10:00:00.000000Z",
      "updated_at": "2025-04-27T10:00:00.000000Z"
    }
  ]
}
```

---

### POST /products

Membuat produk baru.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `code` | string | Ya | Kode produk unik |
| `name` | string | Ya | Nama produk |
| `description` | string | Ya | Deskripsi produk |
| `price` | number | Ya | Harga produk |
| `stock` | integer | Ya | Jumlah stok awal |

```bash
curl -X POST http://127.0.0.1:8001/api/products \
  -H "Content-Type: application/json" \
  -d '{
    "code": "PROD-001",
    "name": "Laptop",
    "description": "High performance laptop",
    "price": 1500.00,
    "stock": 10
  }'
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Product created successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "code": "PROD-001",
    "name": "Laptop",
    "description": "High performance laptop",
    "price": "1500.00",
    "stock": 10,
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
    "code": ["The code field is required."],
    "name": ["The name field is required."],
    "description": ["The description field is required."],
    "price": ["The price field is required."],
    "stock": ["The stock field is required."]
  },
  "data": null
}
```

---

### GET /products/{id}

Mengambil data produk berdasarkan UUID.

```bash
curl -X GET http://127.0.0.1:8001/api/products/550e8400-e29b-41d4-a716-446655440000
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Product found",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "code": "PROD-001",
    "name": "Laptop",
    "description": "High performance laptop",
    "price": "1500.00",
    "stock": 10,
    "created_at": "2025-04-27T10:00:00.000000Z",
    "updated_at": "2025-04-27T10:00:00.000000Z"
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "Product not found",
  "data": null
}
```

---

### PUT /products/{id}

Memperbarui data produk berdasarkan UUID.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `code` | string | Tidak | Kode produk |
| `name` | string | Tidak | Nama produk |
| `description` | string | Tidak | Deskripsi produk |
| `price` | number | Tidak | Harga produk |
| `stock` | integer | Tidak | Jumlah stok |

```bash
curl -X PUT http://127.0.0.1:8001/api/products/550e8400-e29b-41d4-a716-446655440000 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laptop Pro",
    "price": 2000.00,
    "stock": 5
  }'
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Product updated successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "code": "PROD-001",
    "name": "Laptop Pro",
    "description": "High performance laptop",
    "price": "2000.00",
    "stock": 5,
    "created_at": "2025-04-27T10:00:00.000000Z",
    "updated_at": "2025-04-27T10:30:00.000000Z"
  }
}
```

---

### DELETE /products/{id}

Menghapus produk berdasarkan UUID.

```bash
curl -X DELETE http://127.0.0.1:8001/api/products/550e8400-e29b-41d4-a716-446655440000
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Product deleted successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "code": "PROD-001",
    "name": "Laptop"
  }
}
```

---

### POST /products/{uuid}/update-stock

Mengurangi stok produk berdasarkan jumlah yang dipesan. Endpoint ini dipanggil secara langsung oleh **Order Service** saat pesanan dibuat.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `product_quantity` | integer | Ya | Jumlah stok yang dikurangi |

```bash
curl -X POST http://127.0.0.1:8001/api/products/550e8400-e29b-41d4-a716-446655440000/update-stock \
  -H "Content-Type: application/json" \
  -d '{
    "product_quantity": 2
  }'
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Product stock updated successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "code": "PROD-001",
    "name": "Laptop",
    "description": "High performance laptop",
    "price": "1500.00",
    "stock": 8,
    "created_at": "2025-04-27T10:00:00.000000Z",
    "updated_at": "2025-04-27T10:30:00.000000Z"
  }
}
```

**Response Gagal:**
```json
{
  "status": "Failed",
  "message": "Product not found",
  "data": null
}
```

> **Catatan:** Berbeda dengan versi async (`async-docker/product-service`) yang memiliki `sleep(5)` untuk mensimulasikan proses lambat, endpoint ini langsung memperbarui stok tanpa delay. Pembaruan stok pada versi sinkron ini bersifat **blocking** — Order Service menunggu endpoint ini selesai sebelum mengembalikan respons ke klien.

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
php artisan serve --port=8001
```

Service akan berjalan di `http://127.0.0.1:8001` dan siap menerima request dari Order Service maupun klien langsung.
