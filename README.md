# Doa Perkata

Aplikasi web untuk membantu memahami doa harian melalui arti setiap kata, bukan hanya terjemahan utuh. Setiap doa dapat dilihat dalam teks Arab, transliterasi, arti per kata, terjemahan lengkap, dan referensi sumber jika tersedia.

## Fitur

- Menelusuri dan mencari doa berdasarkan judul, teks Arab, atau transliterasi.
- Menyaring doa berdasarkan kategori.
- Membaca teks Arab, transliterasi, terjemahan lengkap, dan arti per kata.
- Menampilkan atau menyembunyikan arti per kata.
- Melihat penanda draf AI pada arti yang belum diverifikasi.
- Memilih doa secara acak.
- Membuat akun, masuk, dan menyimpan doa untuk dibaca kembali.
- Mengelola doa, kategori, pengguna, dan pengaturan melalui panel admin MoonShine.
- Mengimpor kumpulan doa dan membuat draf arti per kata melalui API AI yang dikonfigurasi admin.

## Teknologi

- PHP 8.3 atau lebih baru dan Laravel 13
- SQLite sebagai konfigurasi database bawaan
- MoonShine 4 untuk panel admin
- Vite dan Tailwind CSS 4 untuk aset frontend

## Menjalankan Secara Lokal

Pastikan PHP, Composer, Node.js, dan npm sudah terpasang. Dari direktori proyek, jalankan:

```bash
composer run setup
```

Skrip setup memasang dependensi, membuat `.env` jika belum ada, membuat application key, menjalankan migrasi, memasang dependensi frontend, dan membangun aset.

Jika setup tidak membuat database SQLite secara otomatis, buat berkasnya dengan `touch database/database.sqlite`, lalu jalankan `php artisan migrate`.

Jalankan aplikasi:

```bash
php artisan serve
```

Buka URL yang ditampilkan Artisan, biasanya `http://127.0.0.1:8000`.

## Mengimpor Doa

Database baru tidak berisi data doa. Impor kategori yang diperlukan; perintah akan mengambil data Indonesia dari dataset [fitrahive/dua-dhikr](https://github.com/fitrahive/dua-dhikr):

```bash
php artisan import:dua-dhikr daily-dua
```

Kategori yang tersedia:

- `daily-dua` — Doa Harian
- `selected-dua` — Doa Pilihan
- `morning-dhikr` — Dzikir Pagi
- `evening-dhikr` — Dzikir Petang
- `dhikr-after-salah` — Dzikir Setelah Shalat

Untuk mengimpor dari salinan dataset lokal, gunakan opsi `--path` dan arahkan ke direktori root hasil clone:

```bash
php artisan import:dua-dhikr daily-dua --path=/path/ke/dua-dhikr
```

## Membuat Arti Per Kata

Perintah berikut meminta API AI untuk menghasilkan draf arti per kata bagi satu doa:

```bash
php artisan generate:kata-doa ID_DOA
```

Untuk semua doa yang belum memiliki arti per kata:

```bash
php artisan generate:kata-doa --semua
```

Atur URL API, nama model, dan token opsional pada menu Pengaturan di panel admin sebelum menjalankan perintah. Hasil dari AI berstatus `diterjemahkan_ai`; periksa dan verifikasi hasilnya sebelum dianggap final. Opsi `--ulang` membuat ulang semua kata untuk doa yang diproses, termasuk menghapus entri yang sudah terverifikasi.

## Pengujian

Jalankan pengujian aplikasi dengan:

```bash
php artisan test
```
