
# RESTful API

Folder ini berisi contoh implementasi **REST API** menggunakan arsitektur **monolitik** dengan Laravel 10 dan MySQL. Cocok sebagai titik awal untuk memahami konsep dasar pembuatan API sebelum mempelajari arsitektur yang lebih kompleks seperti microservices.

## Daftar Isi

- [Apa itu REST API?](#apa-itu-rest-api)
- [Struktur Folder](#struktur-folder)
- [Dokumentasi Per Proyek](#dokumentasi-per-proyek)

---

## Apa itu REST API?

**REST (Representational State Transfer)** adalah gaya arsitektur untuk membangun layanan web yang menggunakan protokol HTTP. API berbasis REST menggunakan method HTTP standar untuk melakukan operasi pada sumber daya (resource):

| HTTP Method | Operasi | Keterangan |
|-------------|---------|------------|
| `GET` | Read | Mengambil data |
| `POST` | Create | Membuat data baru |
| `PUT` | Update | Memperbarui data yang ada |
| `DELETE` | Delete | Menghapus data |

Pada arsitektur **monolitik**, seluruh fitur aplikasi — routing, logika bisnis, dan akses database — berada dalam satu aplikasi Laravel dengan satu database MySQL.

---

## Struktur Folder

```
restful-api/
└── student-api/    ← REST API CRUD data mahasiswa
```

---

## Dokumentasi Per Proyek

| Proyek | Deskripsi | README |
|--------|-----------|--------|
| **Student API** | REST API CRUD untuk data mahasiswa | [student-api/README.md](student-api/README.md) |
