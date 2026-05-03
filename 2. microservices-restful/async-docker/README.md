
# Microservices dengan Komunikasi Asinkron (RabbitMQ + Docker)

Contoh implementasi microservices dengan Docker (multi-repo) dan RabbitMQ untuk komunikasi asinkron antar service.

## Daftar Isi

- [Struktur Folder](#struktur-folder)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Dependency Penting](#dependency-penting)
- [Tiga Service Utama](#tiga-service-utama)
- [Docker Container dan Port](#docker-container-dan-port)
- [Struktur Database](#struktur-database)
- [Daftar Endpoint API](#daftar-endpoint-api)
- [Format Response Standar](#format-response-standar)
- [Alur Komunikasi Asinkron](#alur-komunikasi-asinkron)
- [Alur Komunikasi Sinkron](#alur-komunikasi-sinkron)
- [Docker Network](#docker-network)
- [Perbedaan dengan Pendekatan Sinkron](#perbedaan-dengan-pendekatan-sinkron)
- [Dokumentasi Per Service](#dokumentasi-per-service)
- [Cara Menjalankan](#cara-menjalankan)

---

## Struktur Folder

```
async-docker/
├── rabbitmq/           ← Pengaturan message broker
├── user-service/       ← Service manajemen user
├── product-service/    ← Service manajemen produk + queue worker
├── order-service/      ← Service pembuatan order (orchestrator)
├── start-all.sh        ← Skrip untuk menjalankan semua service
└── remove-all.sh       ← Skrip untuk menghapus semua container
```

---

## Teknologi yang Digunakan

| Teknologi | Fungsi |
|-----------|--------|
| **Laravel 10** | Framework untuk tiap service |
| **RabbitMQ** | Message broker untuk komunikasi asinkron |
| **MySQL 8** | Database terisolasi per service |
| **Nginx** | Web server / reverse proxy |
| **Docker** | Kontainerisasi tiap service |
| **PHP 8.2-FPM** | Runtime PHP |

---

## Dependency Penting

Semua service menggunakan dependency berikut:

| Package | Fungsi |
|---------|--------|
| `laravel/framework ^10.0` | Core Laravel framework |
| `laravel/sanctum ^3.2` | API token authentication |
| `guzzlehttp/guzzle ^7.2` | HTTP client untuk komunikasi antar service |

Khusus **Product Service** dan **Order Service** (yang menggunakan RabbitMQ):

| Package | Fungsi |
|---------|--------|
| `vladimir-yuldashev/laravel-queue-rabbitmq ^14.2` | Driver RabbitMQ untuk Laravel Queue |

---

## Tiga Service Utama

### 1. User Service (port 8000)
- Operasi CRUD untuk data pengguna
- UUID sebagai primary key
- Tidak menggunakan antrian (`QUEUE_CONNECTION=sync`)
- Endpoint: `GET/POST /api/users`, `GET/PUT/DELETE /api/users/{id}`

### 2. Product Service (port 8001)
- Operasi CRUD untuk data produk
- Menjalankan queue worker yang mendengarkan RabbitMQ
- Job `UpdateProductStock` memproses pengurangan stok secara asinkron
- Antrian: `product-stock-update`

### 3. Order Service (port 8002)
- Titik masuk utama alur bisnis
- Mengirim job ke RabbitMQ saat pesanan dibuat
- Melakukan pemanggilan HTTP ke service lain untuk mengambil data produk dan pengguna

---

## Docker Container dan Port

### RabbitMQ

| Nama Container | Image | Port |
|----------------|-------|------|
| `rabbitmq` | `rabbitmq:3-management` | `5672` (AMQP), `15672` (Management UI) |

### User Service

| Nama Container | Image | Port |
|----------------|-------|------|
| `user-service-app` | Laravel custom (PHP 8.2-FPM) | Internal |
| `user-service-nginx` | `nginx:stable-alpine` | `8000:80` |
| `user-service-db` | `mysql:8` | `3307:3306` |

### Product Service

| Nama Container | Image | Port |
|----------------|-------|------|
| `product-service-app` | Laravel custom (PHP 8.2-FPM) | Internal |
| `product-service-nginx` | `nginx:stable-alpine` | `8001:80` |
| `product-service-db` | `mysql:8` | `3308:3306` |

### Order Service

| Nama Container | Image | Port |
|----------------|-------|------|
| `order-service-app` | Laravel custom (PHP 8.2-FPM) | Internal |
| `order-service-nginx` | `nginx:stable-alpine` | `8002:80` |
| `order-service-db` | `mysql:8` | `3309:3306` |

---

## Struktur Database

### User Service — Tabel `users`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID) |
| `name` | VARCHAR(255) | Nama pengguna |
| `email` | VARCHAR(255) | Email unik |
| `email_verified_at` | TIMESTAMP | Waktu verifikasi email |
| `password` | VARCHAR(255) | Password (di-hash dengan bcrypt) |
| `remember_token` | VARCHAR(100) | Token remember me |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

### Product Service — Tabel `products`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID) |
| `code` | VARCHAR(255) | Kode produk unik |
| `name` | VARCHAR(255) | Nama produk |
| `description` | VARCHAR(255) | Deskripsi produk |
| `price` | DECIMAL(10,2) | Harga produk |
| `stock` | INT | Jumlah stok |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

### Order Service — Tabel `orders`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID) |
| `code` | VARCHAR(255) | Kode order unik (format: `OR-XXXXXXXX`) |
| `product_id` | VARCHAR(255) | UUID produk dari product service |
| `user_id` | VARCHAR(255) | UUID pengguna dari user service |
| `status` | VARCHAR(255) | Status order (default: `pending`) |
| `total_price` | DECIMAL(10,2) | Total harga pesanan |
| `quantity` | INT | Jumlah barang dipesan |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

---

## Daftar Endpoint API

### User Service — Base URL: `http://localhost:8000/api`

| Method | Endpoint | Deskripsi | Request Body |
|--------|----------|-----------|--------------|
| GET | `/users` | Ambil semua pengguna | - |
| POST | `/users` | Buat pengguna baru | `name`, `email`, `password` |
| GET | `/users/{id}` | Ambil pengguna berdasarkan ID | - |
| PUT | `/users/{id}` | Perbarui data pengguna | `name`, `password` |
| DELETE | `/users/{id}` | Hapus pengguna | - |

**Contoh Request — Buat Pengguna:**
```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

---

### Product Service — Base URL: `http://localhost:8001/api`

| Method | Endpoint | Deskripsi | Request Body |
|--------|----------|-----------|--------------|
| GET | `/products` | Ambil semua produk | - |
| POST | `/products` | Buat produk baru | `code`, `name`, `description`, `price`, `stock` |
| GET | `/products/{id}` | Ambil produk berdasarkan ID | - |
| PUT | `/products/{id}` | Perbarui data produk | `code`, `name`, `description`, `price`, `stock` |
| DELETE | `/products/{id}` | Hapus produk | - |
| POST | `/products/{uuid}/update-stock` | Perbarui stok produk (sinkron, blocking) | `product_quantity` |

**Contoh Request — Buat Produk:**
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

**Contoh Request — Perbarui Stok (sinkron):**
```bash
curl -X POST http://localhost:8001/api/products/{uuid}/update-stock \
  -H "Content-Type: application/json" \
  -d '{
    "product_quantity": 2
  }'
```

---

### Order Service — Base URL: `http://localhost:8002/api`

| Method | Endpoint | Deskripsi | Request Body |
|--------|----------|-----------|--------------|
| GET | `/orders` | Ambil semua pesanan | - |
| POST | `/orders` | Buat pesanan baru (trigger async job) | `product_id`, `user_id`, `status`, `total_price`, `quantity` |
| GET | `/orders/{id}` | Ambil pesanan beserta detail produk & pengguna | - |
| PUT | `/orders/{id}` | Perbarui data pesanan | `product_id`, `user_id`, `status`, `total_price`, `quantity` |
| DELETE | `/orders/{id}` | Hapus pesanan | - |
| GET | `/orders/user/{uuid}` | Ambil semua pesanan milik pengguna tertentu | - |

**Contoh Request — Buat Pesanan:**
```bash
curl -X POST http://localhost:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "product-uuid-disini",
    "user_id": "user-uuid-disini",
    "status": "pending",
    "total_price": 3000.00,
    "quantity": 2
  }'
```

---

## Format Response Standar

Semua endpoint menggunakan format response yang konsisten:

```json
{
  "status": "Success",
  "message": "Deskripsi pesan",
  "data": {}
}
```

**Contoh Response Berhasil:**
```json
{
  "status": "Success",
  "message": "Order created successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "code": "OR-AbXyZq12",
    "product_id": "product-uuid",
    "user_id": "user-uuid",
    "status": "pending",
    "total_price": "3000.00",
    "quantity": 2,
    "created_at": "2025-04-27T10:50:00Z",
    "updated_at": "2025-04-27T10:50:00Z"
  }
}
```

**Contoh Response Gagal (Validasi):**
```json
{
  "status": "Failed",
  "message": "Validation errors",
  "data": {
    "name": ["The name field is required."],
    "email": ["The email must be a valid email address."]
  }
}
```

**Contoh Response `GET /orders/{id}` — Data diperkaya dari service lain:**
```json
{
  "status": "Success",
  "message": "Order found",
  "data": {
    "id": "order-uuid",
    "code": "OR-AbXyZq12",
    "status": "pending",
    "total_price": "3000.00",
    "quantity": 2,
    "product": {
      "id": "product-uuid",
      "code": "PROD-001",
      "name": "Laptop",
      "price": "1500.00",
      "stock": 8
    },
    "user": {
      "id": "user-uuid",
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

## Alur Komunikasi Asinkron

```
Klien → POST /api/orders (Order Service)
              ↓
      Simpan pesanan ke DB
              ↓
  Kirim UpdateProductStock ke RabbitMQ ←── non-blocking, langsung merespons!
              ↓
    Antrian RabbitMQ: "product-stock-update"
              ↓
  Queue Worker Product Service (queue:work)
              ↓
      Kurangi stok produk
```

Keuntungan pola ini: Order service langsung merespons ke klien tanpa menunggu proses pembaruan stok selesai (yang bisa memakan waktu lama).

---

## Alur Komunikasi Sinkron

Saat `GET /api/orders/{id}`, order service melakukan pemanggilan HTTP ke service lain:

```
Order Service → HTTP GET product-service-nginx/api/products/{id}
Order Service → HTTP GET user-service-nginx/api/users/{id}
             → Gabungkan semua data → Kembalikan ke klien
```

---

## Docker Network

Semua container terhubung melalui jaringan bersama `laravel-net`, sehingga antar container dapat berkomunikasi menggunakan nama service (DNS internal Docker), contoh: `product-service-nginx`, `rabbitmq`.

Hostname internal tiap service:

| Service | Hostname Internal |
|---------|-------------------|
| User Service | `user-service-nginx` |
| Product Service | `product-service-nginx` |
| Order Service | `order-service-nginx` |
| RabbitMQ | `rabbitmq` |

---

## Perbedaan dengan Pendekatan Sinkron

Folder ini menunjukkan perbandingan antara dua pendekatan:
- `ProductController.php` memiliki method `updateStock()` dengan `sleep(5)` — mensimulasikan operasi lambat secara **sinkron** (blocking)
- `OrderController.php` mengirim job ke RabbitMQ — pendekatan **asinkron** (non-blocking)

Ini adalah contoh pembelajaran yang sangat baik untuk memahami perbedaan antara komunikasi sinkron dan asinkron dalam arsitektur microservices.

---

## Dokumentasi Per Service

Setiap service dan message broker memiliki README tersendiri yang menjelaskan detail lebih lengkap, termasuk struktur container, konfigurasi environment, skema database, dan contoh request/response per endpoint.

| Service | README |
|---------|--------|
| RabbitMQ | [rabbitmq/README.md](rabbitmq/README.md) |
| User Service | [user-service/README.md](user-service/README.md) |
| Product Service | [product-service/README.md](product-service/README.md) |
| Order Service | [order-service/README.md](order-service/README.md) |

---

## Cara Menjalankan

### Menjalankan Semua Service

Jalankan skrip berikut dari folder `async-docker/`:

```bash
./start-all.sh
```

Skrip ini akan otomatis:
1. Membuat Docker network `laravel-net` (jika belum ada)
2. Menjalankan container RabbitMQ, user-service, product-service, dan order-service
3. Menjalankan migrasi dan seeder database untuk setiap service
4. Menjalankan queue worker pada product-service

### Menghentikan dan Menghapus Semua Service

```bash
./remove-all.sh
```

Skrip ini akan menghentikan semua container, menghapus image, volume, dan Docker network `laravel-net`.

### Membuka RabbitMQ Management UI

Setelah container berjalan, buka browser dan akses:

```
http://localhost:15672
```

Login menggunakan kredensial berikut:
- **Username:** `guest`
- **Password:** `guest`

### Simulasi Komunikasi Asinkron

Setelah semua service berjalan, kirim banyak permintaan secara terus-menerus ke endpoint berikut untuk mensimulasikan komunikasi asinkron:

```
POST http://localhost:8002/api/orders
```
