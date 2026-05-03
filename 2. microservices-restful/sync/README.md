
# Microservices dengan Komunikasi Sinkron (HTTP)

Contoh implementasi arsitektur microservices menggunakan komunikasi **sinkron** antar service melalui HTTP request langsung. Folder ini adalah pasangan dari folder `async-docker/` dan bertujuan untuk memberikan perbandingan antara pendekatan sinkron dan asinkron dalam arsitektur microservices.

## Daftar Isi

- [Perbedaan dengan Versi Asinkron](#perbedaan-dengan-versi-asinkron)
- [Struktur Folder](#struktur-folder)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Tiga Service Utama](#tiga-service-utama)
- [Konfigurasi Database](#konfigurasi-database)
- [Struktur Database](#struktur-database)
- [Komunikasi Antar Service](#komunikasi-antar-service)
- [Daftar Endpoint API](#daftar-endpoint-api)
- [Format Response Standar](#format-response-standar)
- [Cara Menjalankan](#cara-menjalankan)

---

## Perbedaan dengan Versi Asinkron

Folder ini **tidak menggunakan Docker** dan **tidak menggunakan RabbitMQ**. Service dijalankan langsung di mesin lokal dan berkomunikasi satu sama lain melalui HTTP secara sinkron (blocking).

| Aspek | Sinkron (`/sync`) | Asinkron (`/async-docker`) |
|-------|-------------------|---------------------------|
| **Infrastruktur** | Lokal (tanpa Docker) | Docker + Docker Network |
| **Komunikasi** | HTTP langsung antar service | RabbitMQ sebagai message broker |
| **Update Stok** | HTTP POST blocking ke Product Service | Dispatch job ke antrian RabbitMQ |
| **Respons** | Menunggu semua proses selesai | Langsung merespons, proses berjalan di background |
| **Kompleksitas** | Lebih sederhana | Lebih kompleks, lebih scalable |
| **Kelemahan** | Lambat jika salah satu service lambat | Lebih sulit di-debug |

---

## Struktur Folder

```
sync/
├── user-service/       ← Service manajemen pengguna (port 8000)
├── product-service/    ← Service manajemen produk (port 8001)
└── order-service/      ← Service pembuatan pesanan (port 8002)
```

Setiap service adalah aplikasi Laravel yang berdiri sendiri dengan database MySQL masing-masing.

---

## Teknologi yang Digunakan

| Teknologi | Fungsi |
|-----------|--------|
| **Laravel 10** | Framework untuk tiap service |
| **PHP 8.1+** | Runtime PHP |
| **MySQL** | Database terisolasi per service |
| **Guzzle HTTP** | HTTP client untuk komunikasi antar service |
| **Laravel Sanctum** | API token authentication |

**Dependency (semua service):**

| Package | Fungsi |
|---------|--------|
| `laravel/framework ^10.0` | Core Laravel framework |
| `laravel/sanctum ^3.2` | API token authentication |
| `guzzlehttp/guzzle ^7.2` | HTTP client untuk komunikasi antar service |

---

## Tiga Service Utama

### 1. User Service (port 8000)
- Operasi CRUD untuk data pengguna
- UUID sebagai primary key
- Database: `user-service`
- Tidak melakukan panggilan ke service lain

### 2. Product Service (port 8001)
- Operasi CRUD untuk data produk
- UUID sebagai primary key
- Database: `product-service`
- Menyediakan endpoint `update-stock` yang dipanggil oleh Order Service secara sinkron

### 3. Order Service (port 8002)
- Titik masuk utama alur bisnis
- Database: `order-service`
- Berkomunikasi langsung ke User Service dan Product Service melalui HTTP

---

## Konfigurasi Database

Setiap service menggunakan database MySQL yang terpisah dan berjalan di mesin lokal.

| Service | Database | Host | Port |
|---------|----------|------|------|
| User Service | `user-service` | `127.0.0.1` | `3306` |
| Product Service | `product-service` | `127.0.0.1` | `3306` |
| Order Service | `order-service` | `127.0.0.1` | `3306` |

Kredensial default: `root` / `root`

---

## Struktur Database

### User Service — Tabel `users`

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

### Product Service — Tabel `products`

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

### Order Service — Tabel `orders`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | VARCHAR(36) | Primary key (UUID, otomatis digenerate) |
| `code` | VARCHAR(255) | Kode pesanan unik |
| `product_id` | VARCHAR(255) | UUID produk dari Product Service |
| `user_id` | VARCHAR(255) | UUID pengguna dari User Service |
| `status` | VARCHAR(255) | Status pesanan (default: `pending`) |
| `total_price` | DECIMAL(10,2) | Total harga pesanan |
| `quantity` | INT | Jumlah barang dipesan (default: 1) |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |

---

## Komunikasi Antar Service

Semua komunikasi dilakukan secara **sinkron** melalui HTTP menggunakan alamat `127.0.0.1` (localhost).

### Saat Membuat Pesanan — POST /api/orders

Order Service memanggil Product Service secara langsung dan **menunggu** hingga stok berhasil diperbarui sebelum mengembalikan respons ke klien.

```
Klien → POST /api/orders (Order Service: 8002)
              ↓
      Validasi data pesanan
              ↓
      Simpan pesanan ke DB
              ↓
  HTTP POST → http://127.0.0.1:8001/api/products/{id}/update-stock  ← BLOCKING!
              ↓ (menunggu respons dari Product Service)
      Kembalikan respons ke klien
```

> **Kelemahan:** Jika Product Service lambat atau tidak tersedia, Order Service akan ikut terhenti dan klien harus menunggu.

### Saat Mengambil Detail Pesanan — GET /api/orders/{id}

Order Service memanggil Product Service dan User Service untuk memperkaya data pesanan.

```
Klien → GET /api/orders/{id} (Order Service: 8002)
              ↓
      Ambil data pesanan dari DB
              ↓
  HTTP GET → http://127.0.0.1:8001/api/products/{product_id}  ← BLOCKING!
              ↓
  HTTP GET → http://127.0.0.1:8000/api/users/{user_id}        ← BLOCKING!
              ↓
      Gabungkan semua data → Kembalikan ke klien
```

---

## Daftar Endpoint API

### User Service — Base URL: `http://127.0.0.1:8000/api`

| Method | Endpoint | Deskripsi | Request Body |
|--------|----------|-----------|--------------|
| GET | `/users` | Ambil semua pengguna | - |
| POST | `/users` | Buat pengguna baru | `name`, `email`, `password` |
| GET | `/users/{id}` | Ambil pengguna berdasarkan ID | - |
| PUT | `/users/{id}` | Perbarui data pengguna | `name`, `password` |
| DELETE | `/users/{id}` | Hapus pengguna | - |

**Contoh Request — Buat Pengguna:**
```bash
curl -X POST http://127.0.0.1:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

---

### Product Service — Base URL: `http://127.0.0.1:8001/api`

| Method | Endpoint | Deskripsi | Request Body |
|--------|----------|-----------|--------------|
| GET | `/products` | Ambil semua produk | - |
| POST | `/products` | Buat produk baru | `code`, `name`, `description`, `price`, `stock` |
| GET | `/products/{id}` | Ambil produk berdasarkan ID | - |
| PUT | `/products/{id}` | Perbarui data produk | `code`, `name`, `description`, `price`, `stock` |
| DELETE | `/products/{id}` | Hapus produk | - |
| POST | `/products/{uuid}/update-stock` | Kurangi stok produk (dipanggil oleh Order Service) | `product_quantity` |

**Contoh Request — Buat Produk:**
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

---

### Order Service — Base URL: `http://127.0.0.1:8002/api`

| Method | Endpoint | Deskripsi | Request Body |
|--------|----------|-----------|--------------|
| GET | `/orders` | Ambil semua pesanan | - |
| POST | `/orders` | Buat pesanan baru (trigger HTTP ke Product Service) | `product_id`, `user_id`, `status`, `total_price`, `quantity` |
| GET | `/orders/{id}` | Ambil pesanan beserta detail produk & pengguna | - |
| GET | `/orders/user/{uuid}` | Ambil semua pesanan milik pengguna tertentu | - |

**Contoh Request — Buat Pesanan:**
```bash
curl -X POST http://127.0.0.1:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "uuid-produk-disini",
    "user_id": "uuid-user-disini",
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
  "status": "Success | Failed",
  "message": "Pesan deskriptif",
  "data": {} | [] | null
}
```

**Contoh Response Berhasil — Buat Pesanan:**
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

**Contoh Response — Detail Pesanan (diperkaya dari service lain):**
```json
{
  "status": "Success",
  "message": "Order found",
  "data": {
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
}
```

**Contoh Response Gagal (Validasi):**
```json
{
  "status": "Failed",
  "message": {
    "name": ["The name field is required."],
    "email": ["The email field is required."]
  },
  "data": null
}
```

---

## Cara Menjalankan

Karena tidak menggunakan Docker, setiap service dijalankan langsung menggunakan perintah `php artisan serve` di terminal yang berbeda.

### Prasyarat

- PHP 8.1+
- Composer
- MySQL (berjalan di lokal)
- Tiga database MySQL sudah dibuat: `user-service`, `product-service`, `order-service`

### Langkah-langkah

**1. Jalankan User Service (terminal 1)**

```bash
cd user-service
composer install
php artisan migrate:refresh --seed
php artisan serve --port=8000
```

**2. Jalankan Product Service (terminal 2)**

```bash
cd product-service
composer install
php artisan migrate:refresh --seed
php artisan serve --port=8001
```

**3. Jalankan Order Service (terminal 3)**

```bash
cd order-service
composer install
php artisan migrate:refresh
php artisan serve --port=8002
```

### Simulasi Komunikasi Sinkron

Setelah ketiga service berjalan, buat pesanan melalui Order Service dan amati bahwa respons baru diberikan setelah Product Service selesai memperbarui stok:

```bash
curl -X POST http://127.0.0.1:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "uuid-produk-disini",
    "user_id": "uuid-user-disini",
    "status": "pending",
    "total_price": 1500.00,
    "quantity": 1
  }'
```

Bandingkan kecepatan respons ini dengan versi asinkron di folder `async-docker/` untuk memahami perbedaannya secara nyata.
