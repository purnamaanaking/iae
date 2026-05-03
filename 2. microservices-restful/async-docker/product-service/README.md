
# Product Service

Service untuk manajemen data produk dalam arsitektur microservices. Dibangun menggunakan Laravel 10 dan berjalan di dalam container Docker. Service ini juga berperan sebagai **consumer** pesan dari RabbitMQ untuk memproses pembaruan stok produk secara asinkron.

## Daftar Isi

- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Struktur Container](#struktur-container)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Struktur Database](#struktur-database)
- [Queue Job — UpdateProductStock](#queue-job--updateproductstock)
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
| **RabbitMQ** | Message broker untuk menerima job asinkron |
| **Laravel Sanctum** | API token authentication |
| **Docker** | Kontainerisasi service |

**Dependency tambahan:**

| Package | Fungsi |
|---------|--------|
| `vladimir-yuldashev/laravel-queue-rabbitmq ^14.2` | Driver RabbitMQ untuk Laravel Queue |
| `guzzlehttp/guzzle ^7.2` | HTTP client |

---

## Struktur Container

Service ini terdiri dari 3 container Docker:

| Nama Container | Image | Port | Fungsi |
|----------------|-------|------|--------|
| `product-service-app` | PHP 8.2-FPM (custom) | Internal | Menjalankan aplikasi Laravel + queue worker |
| `product-service-nginx` | `nginx:stable-alpine` | `8001:80` | Web server / reverse proxy |
| `product-service-db` | `mysql:8` | `3308:3306` | Database MySQL |

Semua container terhubung melalui network `laravel-net`.

---

## Konfigurasi Environment

| Variabel | Nilai | Keterangan |
|----------|-------|------------|
| `APP_NAME` | `ProductService` | Nama aplikasi |
| `APP_URL` | `http://localhost:8001` | URL aplikasi |
| `DB_HOST` | `product_db` | Hostname database (nama service Docker) |
| `DB_PORT` | `3306` | Port database |
| `DB_DATABASE` | `product_service` | Nama database |
| `DB_USERNAME` | `root` | Username database |
| `DB_PASSWORD` | `secret` | Password database |
| `QUEUE_CONNECTION` | `rabbitmq` | Menggunakan RabbitMQ sebagai driver antrian |
| `RABBITMQ_HOST` | `rabbitmq` | Hostname RabbitMQ (nama container Docker) |
| `RABBITMQ_PORT` | `5672` | Port AMQP RabbitMQ |
| `RABBITMQ_USER` | `guest` | Username RabbitMQ |
| `RABBITMQ_PASSWORD` | `guest` | Password RabbitMQ |
| `RABBITMQ_QUEUE` | `product-stock-update` | Nama antrian yang didengarkan |

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

### Tabel `failed_jobs`

Tabel ini digunakan Laravel untuk mencatat job yang gagal diproses dari antrian.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT | Primary key |
| `uuid` | VARCHAR | UUID job unik |
| `connection` | TEXT | Nama koneksi antrian |
| `queue` | TEXT | Nama antrian |
| `payload` | LONGTEXT | Data job yang gagal |
| `exception` | LONGTEXT | Pesan error |
| `failed_at` | TIMESTAMP | Waktu job gagal |

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

## Queue Job — UpdateProductStock

File: `app/Jobs/UpdateProductStock.php`

Job ini adalah inti dari mekanisme komunikasi asinkron pada Product Service. Job dikirim oleh **Order Service** melalui RabbitMQ, kemudian diproses oleh queue worker yang berjalan di container `product-service-app`.

**Alur kerja:**

```
Order Service → dispatch job → RabbitMQ Queue: "product-stock-update"
                                          ↓
                              Product Service Queue Worker
                                          ↓
                              Product::where('id', $productId)
                                   ->decrement('stock', $quantity)
```

**Parameter job:**

| Parameter | Tipe | Keterangan |
|-----------|------|------------|
| `$productId` | string | UUID produk yang stoknya akan dikurangi |
| `$quantity` | int | Jumlah stok yang dikurangi |

**Menjalankan queue worker secara manual:**

```bash
docker exec -it product-service-app php artisan queue:work
```

---

## Endpoint API

**Base URL:** `http://localhost:8001/api`

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/products` | Ambil semua produk | Tidak |
| POST | `/products` | Buat produk baru | Tidak |
| GET | `/products/{id}` | Ambil produk berdasarkan ID | Tidak |
| PUT | `/products/{id}` | Perbarui data produk | Tidak |
| DELETE | `/products/{id}` | Hapus produk | Tidak |
| POST | `/products/{uuid}/update-stock` | Perbarui stok produk (sinkron, blocking) | Tidak |
| GET | `/user` | Ambil data pengguna yang sedang login | Ya (Sanctum) |

---

### GET /products

Mengambil semua data produk.

```bash
curl -X GET http://localhost:8001/api/products
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
curl -X POST http://localhost:8001/api/products \
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
    "name": ["The name field is required."]
  },
  "data": null
}
```

---

### GET /products/{id}

Mengambil data produk berdasarkan UUID.

```bash
curl -X GET http://localhost:8001/api/products/550e8400-e29b-41d4-a716-446655440000
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
curl -X PUT http://localhost:8001/api/products/550e8400-e29b-41d4-a716-446655440000 \
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
curl -X DELETE http://localhost:8001/api/products/550e8400-e29b-41d4-a716-446655440000
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

Memperbarui stok produk secara **sinkron** (blocking). Endpoint ini mensimulasikan proses yang lama dengan menunggu selama 5 detik sebelum merespons. Tujuannya adalah untuk membandingkan dengan pendekatan asinkron melalui RabbitMQ.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `product_quantity` | integer | Ya | Jumlah stok yang dikurangi |

```bash
curl -X POST http://localhost:8001/api/products/550e8400-e29b-41d4-a716-446655440000/update-stock \
  -H "Content-Type: application/json" \
  -d '{
    "product_quantity": 2
  }'
```

**Response Berhasil** *(setelah menunggu 5 detik)*:
```json
{
  "status": "Success",
  "message": "Product stock updated successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "code": "PROD-001",
    "name": "Laptop",
    "price": "1500.00",
    "stock": 8,
    "created_at": "2025-04-27T10:00:00.000000Z",
    "updated_at": "2025-04-27T10:30:00.000000Z"
  }
}
```

> **Catatan:** Endpoint ini sengaja dibuat lambat (blocking 5 detik) untuk menunjukkan kelemahan pendekatan sinkron. Bandingkan dengan pembaruan stok asinkron yang dilakukan melalui RabbitMQ oleh Order Service — respons langsung diberikan tanpa menunggu proses selesai.

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

### Menjalankan hanya Product Service

```bash
docker-compose up -d --build
```

Kemudian jalankan migrasi dan seeder:

```bash
docker exec -it product-service-app php artisan migrate:refresh --seed
```

Kemudian jalankan queue worker untuk mulai mendengarkan antrian RabbitMQ:

```bash
docker exec -d product-service-app php artisan queue:work
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
