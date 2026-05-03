
# Enterprise Application Integration (EAI)

Kumpulan contoh implementasi pola integrasi aplikasi enterprise menggunakan **Laravel** dan **MySQL**. Repository ini dirancang sebagai bahan pembelajaran untuk memahami berbagai pendekatan dalam membangun dan mengintegrasikan aplikasi, mulai dari arsitektur monolitik hingga microservices dengan komunikasi sinkron dan asinkron.

## Daftar Isi

- [Struktur Repository](#struktur-repository)
- [Topik Pembelajaran](#topik-pembelajaran)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Perbandingan Antar Topik](#perbandingan-antar-topik)
- [Prasyarat](#prasyarat)

---

## Struktur Repository

```
iae/
├── 1. restful-api/
│   └── student-api/            ← REST API monolitik (data mahasiswa)
│
├── 2. microservices-restful/
│   ├── sync/                   ← Microservices komunikasi sinkron (HTTP)
│   │   ├── user-service/
│   │   ├── product-service/
│   │   └── order-service/
│   └── async-docker/           ← Microservices komunikasi asinkron (RabbitMQ + Docker)
│       ├── rabbitmq/
│       ├── user-service/
│       ├── product-service/
│       └── order-service/
│
└── 3. graphql-api/
    └── student-api/            ← GraphQL API monolitik (data mahasiswa)
```

---

## Topik Pembelajaran

### 1. RESTful API — Monolitik

**Folder:** `1. restful-api/student-api/`

Contoh implementasi REST API sederhana menggunakan arsitektur **monolitik**. Semua fitur berada dalam satu aplikasi Laravel dengan satu database MySQL.

| Aspek | Detail |
|-------|--------|
| **Arsitektur** | Monolitik |
| **Protokol** | REST (HTTP) |
| **Database** | MySQL (satu database) |
| **Entitas** | Data mahasiswa |
| **Dijalankan** | `php artisan serve` |

---

### 2. Microservices RESTful

**Folder:** `2. microservices-restful/`

Contoh implementasi arsitektur **microservices** di mana setiap service berdiri sendiri dengan database masing-masing. Terdapat dua pendekatan komunikasi antar service:

#### a. Komunikasi Sinkron — `sync/`

Service berkomunikasi langsung satu sama lain melalui **HTTP request** secara blocking. Dijalankan langsung di mesin lokal tanpa Docker.

| Aspek | Detail |
|-------|--------|
| **Arsitektur** | Microservices |
| **Komunikasi** | HTTP sinkron (blocking) |
| **Message Broker** | Tidak ada |
| **Infrastruktur** | Lokal (tanpa Docker) |
| **Dijalankan** | `php artisan serve` per service |

**Service yang tersedia:**

| Service | Port | Fungsi |
|---------|------|--------|
| User Service | 8000 | Manajemen data pengguna |
| Product Service | 8001 | Manajemen data produk |
| Order Service | 8002 | Manajemen pesanan, memanggil service lain via HTTP |

#### b. Komunikasi Asinkron — `async-docker/`

Service berkomunikasi melalui **RabbitMQ** sebagai message broker. Order Service mengirim job ke antrian tanpa menunggu hasilnya, dan Product Service memproses job tersebut di background. Seluruh service dijalankan dalam container Docker.

| Aspek | Detail |
|-------|--------|
| **Arsitektur** | Microservices |
| **Komunikasi** | Asinkron via RabbitMQ (non-blocking) |
| **Message Broker** | RabbitMQ |
| **Infrastruktur** | Docker + Docker Network (`laravel-net`) |
| **Dijalankan** | `./start-all.sh` |

**Service yang tersedia:**

| Service | Port | Fungsi |
|---------|------|--------|
| RabbitMQ | 5672 / 15672 | Message broker |
| User Service | 8000 | Manajemen data pengguna |
| Product Service | 8001 | Manajemen data produk + queue worker |
| Order Service | 8002 | Manajemen pesanan, dispatch job ke RabbitMQ |

---

### 3. GraphQL API — Monolitik

**Folder:** `3. graphql-api/student-api/`

Contoh implementasi **GraphQL API** menggunakan arsitektur monolitik. Berbeda dengan REST yang memiliki banyak endpoint, GraphQL menggunakan satu endpoint tunggal dengan query yang fleksibel.

| Aspek | Detail |
|-------|--------|
| **Arsitektur** | Monolitik |
| **Protokol** | GraphQL |
| **Database** | MySQL (satu database) |
| **Entitas** | Data mahasiswa |
| **Dijalankan** | `php artisan serve` |

---

## Teknologi yang Digunakan

| Teknologi | Fungsi |
|-----------|--------|
| **Laravel 10** | Framework PHP untuk semua service |
| **PHP 8.1+** | Runtime PHP |
| **MySQL** | Database relasional |
| **RabbitMQ** | Message broker untuk komunikasi asinkron |
| **Docker** | Kontainerisasi service pada topik async |
| **Guzzle HTTP** | HTTP client untuk komunikasi antar service |
| **Laravel Sanctum** | API token authentication |
| **GraphQL** | Query language untuk API fleksibel |

---

## Perbandingan Antar Topik

| Aspek | RESTful Monolitik | Microservices Sinkron | Microservices Asinkron | GraphQL Monolitik |
|-------|------------------|----------------------|------------------------|------------------|
| **Arsitektur** | Monolitik | Microservices | Microservices | Monolitik |
| **Protokol** | REST | REST | REST + RabbitMQ | GraphQL |
| **Database** | Satu | Terpisah per service | Terpisah per service | Satu |
| **Komunikasi** | - | HTTP (blocking) | Queue (non-blocking) | - |
| **Infrastruktur** | Lokal | Lokal | Docker | Lokal |
| **Kompleksitas** | Rendah | Sedang | Tinggi | Sedang |
| **Skalabilitas** | Rendah | Sedang | Tinggi | Rendah |

---

## Prasyarat

### Untuk topik 1, 2 (sync), dan 3:
- PHP 8.1+
- Composer
- MySQL

### Untuk topik 2 (async-docker):
- Docker & Docker Compose
- Semua dependency diinstal otomatis di dalam container

---

## Dokumentasi Per Topik

| Topik | README |
|-------|--------|
| RESTful API (Monolitik) | [1. restful-api/student-api/README.md](1.%20restful-api/student-api/README.md) |
| Microservices Sinkron | [2. microservices-restful/sync/README.md](2.%20microservices-restful/sync/README.md) |
| Microservices Asinkron | [2. microservices-restful/async-docker/README.md](2.%20microservices-restful/async-docker/README.md) |
| GraphQL API (Monolitik) | [3. graphql-api/student-api/README.md](3.%20graphql-api/student-api/README.md) |
