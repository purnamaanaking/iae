
# Microservices RESTful

Folder ini berisi contoh implementasi arsitektur **microservices** menggunakan REST API dengan Laravel 10 dan MySQL. Terdapat dua pendekatan komunikasi antar service yang disajikan secara berdampingan agar dapat dipelajari dan dibandingkan secara langsung.

## Daftar Isi

- [Apa itu Microservices?](#apa-itu-microservices)
- [Struktur Folder](#struktur-folder)
- [Dua Pendekatan Komunikasi](#dua-pendekatan-komunikasi)
- [Perbandingan Sinkron vs Asinkron](#perbandingan-sinkron-vs-asinkron)
- [Dokumentasi Per Pendekatan](#dokumentasi-per-pendekatan)

---

## Apa itu Microservices?

**Microservices** adalah arsitektur perangkat lunak di mana sebuah aplikasi dibangun sebagai kumpulan service-service kecil yang berdiri sendiri, masing-masing memiliki database dan logika bisnis tersendiri, serta berkomunikasi satu sama lain melalui jaringan.

Berbeda dengan arsitektur **monolitik** (semua fitur dalam satu aplikasi), microservices memisahkan setiap domain bisnis menjadi service tersendiri:

```
Monolitik                        Microservices
─────────────────                ─────────────────────────────────
┌─────────────────┐              ┌──────────┐  ┌──────────────┐
│                 │              │   User   │  │   Product    │
│  Satu Aplikasi  │              │  Service │  │   Service    │
│  Satu Database  │    vs.       └──────────┘  └──────────────┘
│                 │                    ↕              ↕
└─────────────────┘              ┌──────────────────────────┐
                                 │       Order Service       │
                                 └──────────────────────────┘
```

**Keuntungan microservices:**
- Setiap service dapat dikembangkan dan di-deploy secara independen
- Skala hanya pada service yang membutuhkan (bukan seluruh aplikasi)
- Kegagalan satu service tidak langsung mematikan seluruh sistem

**Kekurangan microservices:**
- Lebih kompleks dalam hal infrastruktur dan komunikasi antar service
- Membutuhkan strategi yang matang untuk menangani kegagalan jaringan

---

## Struktur Folder

```
microservices-restful/
├── sync/                   ← Komunikasi sinkron (HTTP langsung, tanpa Docker)
│   ├── user-service/
│   ├── product-service/
│   └── order-service/
│
└── async-docker/           ← Komunikasi asinkron (RabbitMQ + Docker)
    ├── rabbitmq/
    ├── user-service/
    ├── product-service/
    └── order-service/
```

---

## Dua Pendekatan Komunikasi

### 1. Sinkron — `sync/`

Service berkomunikasi langsung satu sama lain melalui **HTTP request** secara blocking. Setiap request menunggu respons dari service tujuan sebelum melanjutkan proses.

```
Order Service → HTTP POST → Product Service (menunggu...) → selesai → respons ke klien
```

- Dijalankan langsung di mesin lokal menggunakan `php artisan serve`
- Tidak membutuhkan Docker atau message broker
- Lebih mudah dipahami sebagai titik awal belajar microservices

### 2. Asinkron — `async-docker/`

Service berkomunikasi melalui **RabbitMQ** sebagai message broker. Order Service mengirim pesan ke antrian dan langsung merespons ke klien tanpa menunggu proses selesai. Product Service memproses pesan tersebut di background.

```
Order Service → dispatch job → RabbitMQ → (di background) Product Service
             ↓
        langsung respons ke klien
```

- Dijalankan menggunakan Docker dan Docker Compose
- Menggunakan RabbitMQ sebagai message broker
- Lebih kompleks namun lebih scalable dan fault-tolerant

---

## Perbandingan Sinkron vs Asinkron

| Aspek | Sinkron (`sync/`) | Asinkron (`async-docker/`) |
|-------|-------------------|---------------------------|
| **Infrastruktur** | Lokal (tanpa Docker) | Docker + Docker Network |
| **Message Broker** | Tidak ada | RabbitMQ |
| **Update Stok** | HTTP POST blocking | Dispatch job ke antrian |
| **Respons ke Klien** | Setelah semua proses selesai | Langsung, proses berjalan di background |
| **Kompleksitas** | Rendah | Tinggi |
| **Skalabilitas** | Sedang | Tinggi |
| **Kelemahan** | Lambat jika salah satu service lambat | Lebih sulit di-debug |

---

## Dokumentasi Per Pendekatan

| Pendekatan | Deskripsi | README |
|------------|-----------|--------|
| **Sinkron** | Microservices dengan HTTP langsung, tanpa Docker | [sync/README.md](sync/README.md) |
| **Asinkron** | Microservices dengan RabbitMQ dan Docker | [async-docker/README.md](async-docker/README.md) |
