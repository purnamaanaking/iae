
# RabbitMQ — Message Broker

Folder ini berisi konfigurasi Docker untuk menjalankan RabbitMQ sebagai message broker dalam arsitektur microservices.

## Daftar Isi

- [Apa itu RabbitMQ?](#apa-itu-rabbitmq)
- [Konfigurasi Container](#konfigurasi-container)
- [Port yang Digunakan](#port-yang-digunakan)
- [Kredensial Default](#kredensial-default)
- [Membuka Management UI](#membuka-management-ui)
- [Antrian yang Digunakan](#antrian-yang-digunakan)
- [Cara Menjalankan](#cara-menjalankan)

---

## Apa itu RabbitMQ?

RabbitMQ adalah message broker open-source yang memungkinkan komunikasi asinkron antar service. Dalam arsitektur ini, RabbitMQ berperan sebagai perantara antara **Order Service** (pengirim pesan) dan **Product Service** (penerima pesan).

Alur kerjanya:
1. Order Service mengirim pesan (job) ke antrian RabbitMQ
2. RabbitMQ menyimpan pesan di antrian
3. Product Service mengambil dan memproses pesan secara asinkron

---

## Konfigurasi Container

| Properti | Nilai |
|----------|-------|
| **Image** | `rabbitmq:3-management` |
| **Nama Container** | `rabbitmq` |
| **Network** | `laravel-net` (eksternal, dibuat oleh `start-all.sh`) |

---

## Port yang Digunakan

| Port | Protokol | Fungsi |
|------|----------|--------|
| `5672` | AMQP | Komunikasi antar service (pengiriman & penerimaan pesan) |
| `15672` | HTTP | RabbitMQ Management UI (antarmuka web) |

---

## Kredensial Default

| Properti | Nilai |
|----------|-------|
| **Username** | `guest` |
| **Password** | `guest` |

---

## Membuka Management UI

Setelah container berjalan, buka browser dan akses:

```
http://localhost:15672
```

Login menggunakan kredensial di atas. Melalui Management UI, kamu dapat:
- Memantau antrian yang aktif
- Melihat jumlah pesan yang masuk dan diproses
- Memantau status consumer (queue worker)
- Melihat detail koneksi dari tiap service

---

## Antrian yang Digunakan

| Nama Antrian | Pengirim | Penerima |
|--------------|----------|----------|
| `product-stock-update` | Order Service | Product Service |

Antrian ini digunakan untuk mengirim job pembaruan stok produk secara asinkron setiap kali sebuah pesanan baru dibuat.

---

## Cara Menjalankan

### Menjalankan hanya RabbitMQ

```bash
docker-compose up -d --build
```

### Menghentikan RabbitMQ

```bash
docker-compose down
```

### Menghentikan dan menghapus semua data (image + volume)

```bash
docker-compose down --rmi all -v
```

> **Catatan:** Pada praktik normalnya, RabbitMQ dijalankan bersama semua service sekaligus menggunakan skrip `start-all.sh` dari folder `async-docker/`.
