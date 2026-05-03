
# GraphQL API

Folder ini berisi contoh implementasi **GraphQL API** menggunakan arsitektur **monolitik** dengan Laravel 10, Lighthouse, dan MySQL. Cocok sebagai perbandingan dengan REST API untuk memahami perbedaan pendekatan dalam membangun dan mengonsumsi API.

## Daftar Isi

- [Apa itu GraphQL?](#apa-itu-graphql)
- [GraphQL vs REST API](#graphql-vs-rest-api)
- [Struktur Folder](#struktur-folder)
- [Dokumentasi Per Proyek](#dokumentasi-per-proyek)

---

## Apa itu GraphQL?

**GraphQL** adalah query language untuk API yang dikembangkan oleh Facebook. Berbeda dengan REST yang memiliki banyak endpoint, GraphQL hanya menggunakan **satu endpoint tunggal** dan memungkinkan klien untuk menentukan sendiri data apa yang ingin diambil.

GraphQL memiliki dua jenis operasi utama:

| Operasi | Fungsi | Setara REST |
|---------|--------|-------------|
| **Query** | Mengambil data (read) | `GET` |
| **Mutation** | Membuat, memperbarui, atau menghapus data | `POST`, `PUT`, `DELETE` |

**Contoh perbedaan:**

Pada REST, untuk mengambil hanya nama dan email mahasiswa, server tetap mengembalikan semua field:
```json
{ "id": 1, "nim": "123", "name": "John", "email": "john@example.com", "address": "...", "phone": "..." }
```

Pada GraphQL, klien menentukan sendiri field yang dibutuhkan:
```graphql
{ student(id: 1) { name email } }
```
```json
{ "data": { "student": { "name": "John", "email": "john@example.com" } } }
```

---

## GraphQL vs REST API

| Aspek | REST API | GraphQL |
|-------|----------|---------|
| **Endpoint** | Banyak (`/students`, `/students/{id}`, dll.) | Satu (`/graphql`) |
| **Pengambilan Data** | Server menentukan data yang dikembalikan | Klien menentukan field yang dibutuhkan |
| **Over-fetching** | Sering terjadi (data berlebih) | Tidak terjadi |
| **Under-fetching** | Sering terjadi (butuh banyak request) | Tidak terjadi |
| **Operasi** | HTTP Method (GET, POST, PUT, DELETE) | Query & Mutation |
| **Dokumentasi** | Manual / Swagger | Otomatis via Introspection |
| **Kompleksitas** | Lebih sederhana | Lebih kompleks di sisi server |

---

## Struktur Folder

```
graphql-api/
└── student-api/    ← GraphQL API CRUD data mahasiswa
```

---

## Dokumentasi Per Proyek

| Proyek | Deskripsi | README |
|--------|-----------|--------|
| **Student API** | GraphQL API CRUD untuk data mahasiswa menggunakan Lighthouse | [student-api/README.md](student-api/README.md) |
