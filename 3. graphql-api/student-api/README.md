
# Student API — GraphQL Monolitik

Contoh implementasi **GraphQL API** menggunakan arsitektur **monolitik** dengan Laravel 10, Lighthouse, dan MySQL. Berbeda dengan REST API yang memiliki banyak endpoint, GraphQL menggunakan **satu endpoint tunggal** (`/graphql`) dengan query yang fleksibel — klien menentukan sendiri data apa yang ingin diambil.

## Daftar Isi

- [Perbedaan GraphQL vs REST API](#perbedaan-graphql-vs-rest-api)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Struktur Database](#struktur-database)
- [GraphQL Schema](#graphql-schema)
- [Operasi GraphQL](#operasi-graphql)
- [Cara Menjalankan](#cara-menjalankan)

---

## Perbedaan GraphQL vs REST API

| Aspek | REST API | GraphQL |
|-------|----------|---------|
| **Endpoint** | Banyak (`/students`, `/students/{id}`, dll.) | Satu (`/graphql`) |
| **Pengambilan Data** | Server menentukan data yang dikembalikan | Klien menentukan field yang dibutuhkan |
| **Over-fetching** | Sering terjadi (data berlebih) | Tidak terjadi |
| **Under-fetching** | Sering terjadi (butuh banyak request) | Tidak terjadi |
| **Operasi** | HTTP Method (GET, POST, PUT, DELETE) | Query & Mutation |
| **Dokumentasi** | Manual / Swagger | Otomatis via Introspection |

---

## Teknologi yang Digunakan

| Teknologi | Fungsi |
|-----------|--------|
| **Laravel 10** | Framework utama |
| **PHP 8.1+** | Runtime PHP |
| **MySQL** | Database |
| **Lighthouse** | Library GraphQL untuk Laravel |
| **Laravel Sanctum** | API token authentication |

**Dependency:**

| Package | Fungsi |
|---------|--------|
| `nuwave/lighthouse ^6.57` | GraphQL server untuk Laravel |
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
| `DB_DATABASE` | `student-gql-api` | Nama database |
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

### Data Awal (Seeder)

Saat migrasi dijalankan dengan `--seed`, akan dibuat **10 mahasiswa dummy** secara otomatis menggunakan factory dengan data acak (nim, name, email, address, phone).

---

## GraphQL Schema

File schema berada di `graphql/schema.graphql`. Schema mendefinisikan struktur data, query, dan mutation yang tersedia.

```graphql
type Student {
  id: ID!
  nim: String!
  name: String!
  email: String!
  address: String!
  phone: String!
  created_at: DateTime
  updated_at: DateTime
}

type Query {
  students: [Student!]!
  student(id: ID!): Student
}

input CreateStudentInput {
  nim: String!
  name: String!
  email: String!
  address: String!
  phone: String!
}

input UpdateStudentInput {
  id: ID!
  nim: String
  name: String
  email: String
  address: String
  phone: String
}

type Mutation {
  createStudent(input: CreateStudentInput!): Student
  updateStudent(input: UpdateStudentInput!): Student
  deleteStudent(id: ID!): Student
}
```

---

## Operasi GraphQL

**Endpoint:** `POST http://127.0.0.1:8000/graphql`

Semua operasi dikirim ke satu endpoint yang sama menggunakan method `POST` dengan body berisi query atau mutation GraphQL.

---

### Query — Ambil Semua Mahasiswa

```bash
curl -X POST http://127.0.0.1:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "{ students { id nim name email address phone } }"
  }'
```

**Response:**
```json
{
  "data": {
    "students": [
      {
        "id": "1",
        "nim": "12345678",
        "name": "John Doe",
        "email": "john@example.com",
        "address": "Jl. Contoh No. 1, Jakarta",
        "phone": "081234567890"
      }
    ]
  }
}
```

> **Catatan:** Klien bebas menentukan field mana yang ingin diambil. Misalnya jika hanya butuh `id` dan `name`, cukup tulis `{ students { id name } }`.

---

### Query — Ambil Mahasiswa Berdasarkan ID

```bash
curl -X POST http://127.0.0.1:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "{ student(id: 1) { id nim name email address phone } }"
  }'
```

**Response:**
```json
{
  "data": {
    "student": {
      "id": "1",
      "nim": "12345678",
      "name": "John Doe",
      "email": "john@example.com",
      "address": "Jl. Contoh No. 1, Jakarta",
      "phone": "081234567890"
    }
  }
}
```

**Response jika tidak ditemukan:**
```json
{
  "data": {
    "student": null
  }
}
```

---

### Mutation — Tambah Mahasiswa Baru

```bash
curl -X POST http://127.0.0.1:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation { createStudent(input: { nim: \"12345678\", name: \"John Doe\", email: \"john@example.com\", address: \"Jl. Contoh No. 1\", phone: \"081234567890\" }) { id nim name email address phone } }"
  }'
```

Atau menggunakan **variabel GraphQL** (cara yang lebih bersih):

```bash
curl -X POST http://127.0.0.1:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation CreateStudent($input: CreateStudentInput!) { createStudent(input: $input) { id nim name email address phone } }",
    "variables": {
      "input": {
        "nim": "12345678",
        "name": "John Doe",
        "email": "john@example.com",
        "address": "Jl. Contoh No. 1, Jakarta",
        "phone": "081234567890"
      }
    }
  }'
```

**Response:**
```json
{
  "data": {
    "createStudent": {
      "id": "1",
      "nim": "12345678",
      "name": "John Doe",
      "email": "john@example.com",
      "address": "Jl. Contoh No. 1, Jakarta",
      "phone": "081234567890"
    }
  }
}
```

---

### Mutation — Perbarui Data Mahasiswa

```bash
curl -X POST http://127.0.0.1:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation UpdateStudent($input: UpdateStudentInput!) { updateStudent(input: $input) { id nim name email address phone } }",
    "variables": {
      "input": {
        "id": "1",
        "name": "John Updated",
        "address": "Jl. Baru No. 2, Bandung"
      }
    }
  }'
```

**Response:**
```json
{
  "data": {
    "updateStudent": {
      "id": "1",
      "nim": "12345678",
      "name": "John Updated",
      "email": "john@example.com",
      "address": "Jl. Baru No. 2, Bandung",
      "phone": "081234567890"
    }
  }
}
```

---

### Mutation — Hapus Mahasiswa

```bash
curl -X POST http://127.0.0.1:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation { deleteStudent(id: 1) { id nim name } }"
  }'
```

**Response:**
```json
{
  "data": {
    "deleteStudent": {
      "id": "1",
      "nim": "12345678",
      "name": "John Doe"
    }
  }
}
```

---

## Cara Menjalankan

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
php artisan serve
```

API GraphQL akan berjalan di `http://127.0.0.1:8000/graphql` dan siap menerima request.
