
# Order Service

Service untuk manajemen data pesanan dalam arsitektur microservices. Dibangun menggunakan Laravel 10 dan berjalan di dalam container Docker. Service ini berperan sebagai **orchestrator** — menerima permintaan pembuatan pesanan, menyimpannya ke database, lalu mengirim job secara asinkron ke RabbitMQ untuk memperbarui stok produk di Product Service.

## Daftar Isi

- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Struktur Container](#struktur-container)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Struktur Database](#struktur-database)
- [Queue Job — UpdateProductStock](#queue-job--updateproductstock)
- [Komunikasi Antar Service](#komunikasi-antar-service)
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
| **RabbitMQ** | Message broker untuk mengirim job asinkron |
| **Guzzle HTTP** | HTTP client untuk mengonsumsi service lain |
| **Laravel Sanctum** | API token authentication |
| **Docker** | Kontainerisasi service |

**Dependency tambahan:**

| Package | Fungsi |
|---------|--------|
| `vladimir-yuldashev/laravel-queue-rabbitmq ^14.2` | Driver RabbitMQ untuk Laravel Queue |
| `guzzlehttp/guzzle ^7.2` | HTTP client untuk komunikasi ke User Service dan Product Service |

---

## Struktur Container

Service ini terdiri dari 3 container Docker:

| Nama Container | Image | Port | Fungsi |
|----------------|-------|------|--------|
| `order-service-app` | PHP 8.2-FPM (custom) | Internal | Menjalankan aplikasi Laravel |
| `order-service-nginx` | `nginx:stable-alpine` | `8002:80` | Web server / reverse proxy |
| `order-service-db` | `mysql:8` | `3309:3306` | Database MySQL |

Semua container terhubung melalui network `laravel-net`.

---

## Konfigurasi Environment

| Variabel | Nilai | Keterangan |
|----------|-------|------------|
| `APP_NAME` | `OrderService` | Nama aplikasi |
| `APP_URL` | `http://localhost:8002` | URL aplikasi |
| `DB_HOST` | `order_db` | Hostname database (nama service Docker) |
| `DB_PORT` | `3306` | Port database |
| `DB_DATABASE` | `order_service` | Nama database |
| `DB_USERNAME` | `root` | Username database |
| `DB_PASSWORD` | `secret` | Password database |
| `QUEUE_CONNECTION` | `rabbitmq` | Menggunakan RabbitMQ sebagai driver antrian |
| `RABBITMQ_HOST` | `rabbitmq` | Hostname RabbitMQ (nama container Docker) |
| `RABBITMQ_PORT` | `5672` | Port AMQP RabbitMQ |
| `RABBITMQ_USER` | `guest` | Username RabbitMQ |
| `RABBITMQ_PASSWORD` | `guest` | Password RabbitMQ |
| `RABBITMQ_QUEUE` | `product-stock-update` | Nama antrian yang digunakan |

---

## Struktur Database

### Tabel `orders`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID, otomatis digenerate) |
| `code` | VARCHAR(255) | Kode pesanan unik (format: `OR-XXXXXXXX`) |
| `product_id` | VARCHAR(255) | UUID produk dari Product Service |
| `user_id` | VARCHAR(255) | UUID pengguna dari User Service |
| `status` | VARCHAR(255) | Status pesanan (default: `pending`) |
| `total_price` | DECIMAL(10,2) | Total harga pesanan |
| `quantity` | INT | Jumlah barang dipesan (default: 1) |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

> `product_id` dan `user_id` disimpan sebagai string tanpa foreign key constraint di database, karena data aslinya berada di service yang berbeda.

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

---

## Queue Job — UpdateProductStock

File: `app/Jobs/UpdateProductStock.php`

Job ini dikirim oleh Order Service ke antrian RabbitMQ setiap kali pesanan baru dibuat. Job kemudian diambil dan diproses oleh queue worker yang berjalan di **Product Service**.

**Parameter job:**

| Parameter | Tipe | Keterangan |
|-----------|------|------------|
| `$productId` | string | UUID produk yang stoknya akan dikurangi |
| `$quantity` | int | Jumlah stok yang dikurangi |

**Cara job dikirim di dalam `OrderController::store()`:**

```php
UpdateProductStock::dispatch($request->product_id, $request->quantity)
    ->onQueue('product-stock-update');
```

**Alur kerja:**

```
POST /api/orders (Order Service)
        ↓
  Simpan pesanan ke DB
        ↓
  dispatch(UpdateProductStock) → RabbitMQ Queue: "product-stock-update"
        ↓
  Langsung return response ke klien (non-blocking)
        ↓
  (di background) Product Service Queue Worker memproses job
        ↓
  Stok produk dikurangi di database Product Service
```

> **Catatan:** Di dalam kode terdapat komentar alternatif pendekatan sinkron menggunakan HTTP langsung ke Product Service. Keduanya sengaja ditampilkan untuk tujuan pembelajaran agar mahasiswa dapat membandingkan kedua pendekatan.

---

## Komunikasi Antar Service

Order Service berkomunikasi dengan dua service lain menggunakan dua cara berbeda:

### Asinkron — via RabbitMQ (Pengirim)

Digunakan saat **membuat pesanan** untuk memperbarui stok produk tanpa memblokir respons.

```
Order Service → dispatch job → RabbitMQ → Product Service Queue Worker
```

### Sinkron — via HTTP (Konsumer)

Digunakan saat **mengambil detail pesanan** untuk memperkaya data dengan informasi produk dan pengguna.

```php
// Mengambil detail produk dari Product Service
Http::get('http://product-service-nginx/api/products/' . $order->product_id);

// Mengambil detail pengguna dari User Service
Http::get('http://user-service-nginx/api/users/' . $order->user_id);
```

| Tujuan | Metode | Kapan Digunakan |
|--------|--------|-----------------|
| Product Service | Asinkron (RabbitMQ) | Saat membuat pesanan — update stok |
| Product Service | Sinkron (HTTP) | Saat mengambil detail pesanan |
| User Service | Sinkron (HTTP) | Saat mengambil detail pesanan |

---

## Endpoint API

**Base URL:** `http://localhost:8002/api`

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/orders` | Ambil semua pesanan | Tidak |
| POST | `/orders` | Buat pesanan baru (trigger async job) | Tidak |
| GET | `/orders/{id}` | Ambil pesanan beserta detail produk & pengguna | Tidak |
| PUT | `/orders/{id}` | Perbarui data pesanan | Tidak |
| DELETE | `/orders/{id}` | Hapus pesanan | Tidak |
| GET | `/orders/user/{uuid}` | Ambil semua pesanan milik pengguna tertentu | Tidak |
| GET | `/user` | Ambil data pengguna yang sedang login | Ya (Sanctum) |

---

### GET /orders

Mengambil semua data pesanan.

```bash
curl -X GET http://localhost:8002/api/orders
```

**Response:**
```json
{
  "status": "Success",
  "message": "List of orders",
  "data": [
    {
      "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "code": "OR-AbXyZq12",
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

Membuat pesanan baru sekaligus mengirim job asinkron ke RabbitMQ untuk memperbarui stok produk.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `product_id` | string | Ya | UUID produk dari Product Service |
| `user_id` | string | Ya | UUID pengguna dari User Service |
| `status` | string | Ya | Status pesanan (contoh: `pending`) |
| `total_price` | number | Ya | Total harga pesanan |
| `quantity` | integer | Ya | Jumlah barang yang dipesan |

```bash
curl -X POST http://localhost:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{
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
    "code": "OR-AbXyZq12",
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

Mengambil detail pesanan berdasarkan UUID, diperkaya dengan data produk dari **Product Service** dan data pengguna dari **User Service** melalui HTTP call.

```bash
curl -X GET http://localhost:8002/api/orders/a1b2c3d4-e5f6-7890-abcd-ef1234567890
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Order found",
  "data": {
    "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "code": "OR-AbXyZq12",
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

Mengambil semua pesanan milik pengguna tertentu berdasarkan UUID pengguna. Setiap pesanan diperkaya dengan data produk dan pengguna dari service lain.

```bash
curl -X GET http://localhost:8002/api/orders/user/660e8400-f39b-41d4-b827-557766551111
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Order found",
  "data": [
    {
      "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "code": "OR-AbXyZq12",
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

### PUT /orders/{id}

Memperbarui data pesanan berdasarkan UUID.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `product_id` | string | Tidak | UUID produk |
| `user_id` | string | Tidak | UUID pengguna |
| `status` | string | Tidak | Status pesanan |
| `total_price` | number | Tidak | Total harga |
| `quantity` | integer | Tidak | Jumlah barang |

```bash
curl -X PUT http://localhost:8002/api/orders/a1b2c3d4-e5f6-7890-abcd-ef1234567890 \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed"
  }'
```

---

### DELETE /orders/{id}

Menghapus pesanan berdasarkan UUID.

```bash
curl -X DELETE http://localhost:8002/api/orders/a1b2c3d4-e5f6-7890-abcd-ef1234567890
```

**Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Order deleted successfully",
  "data": {
    "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "code": "OR-AbXyZq12",
    "status": "pending"
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

### Menjalankan hanya Order Service

```bash
docker-compose up -d --build
```

Kemudian jalankan migrasi:

```bash
docker exec -it order-service-app php artisan migrate:refresh --seed
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

### Simulasi komunikasi asinkron

Setelah semua service berjalan, kirim banyak permintaan secara terus-menerus ke endpoint berikut:

```bash
curl -X POST http://localhost:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "uuid-produk-disini",
    "user_id": "uuid-user-disini",
    "status": "pending",
    "total_price": 1500.00,
    "quantity": 1
  }'
```

Pantau antrian yang masuk dan diproses melalui **RabbitMQ Management UI** di `http://localhost:15672`.
