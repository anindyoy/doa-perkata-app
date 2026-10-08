---
name: fitur-sync
description: "Use whenever adding, modifying, or removing application features in Doa Perkata. Triggers on new routes, controllers, Blade views, MoonShine resources, console commands, auth flows, user roles, permissions, settings keys, migrations, or status enums. Ensures FITUR.md stays in sync with code before completing the task. Also use for pre-completion verification when the diff touches routes/web.php, app/Http/Controllers/, app/Models/, app/MoonShine/, app/Console/Commands/, resources/views/, database/migrations/, or config/auth.php."
license: MIT
metadata:
  author: doa-perkata
---

# Fitur Sync

Pastikan [`FITUR.md`](FITUR.md) selalu mencerminkan kode terbaru setiap ada fitur baru atau penyesuaian fitur.

## Kapan Dipakai

Aktifkan skill ini setiap kali pekerjaan menyentuh salah satu pemicu di bawah, sebelum mengklaim selesai:

- Rute baru, rute diubah, atau rute dihapus di [`routes/web.php`](routes/web.php:9).
- Controller baru atau ubah aksi di [`AuthController.php`](app/Http/Controllers/AuthController.php:12), [`BerandaController.php`](app/Http/Controllers/BerandaController.php:11), [`DoaController.php`](app/Http/Controllers/DoaController.php:11), [`DoaTersimpanController.php`](app/Http/Controllers/DoaTersimpanController.php:10).
- Model atau relasi baru di [`Doa.php`](app/Models/Doa.php:11), [`KataDoa.php`](app/Models/KataDoa.php:9), [`Kategori.php`](app/Models/Kategori.php:8), [`Pengguna.php`](app/Models/Pengguna.php:8), [`Pengaturan.php`](app/Models/Pengaturan.php:8).
- Resource MoonShine baru atau ubah field di [`DoaResource.php`](app/MoonShine/Resources/DoaResource.php:26), [`KataDoaResource.php`](app/MoonShine/Resources/KataDoaResource.php:19), [`KategoriResource.php`](app/MoonShine/Resources/KategoriResource.php:21), [`PenggunaResource.php`](app/MoonShine/Resources/PenggunaResource.php:18), halaman [`PengaturanHalaman.php`](app/MoonShine/Pages/PengaturanHalaman.php:27), atau layout [`AdminLayout.php`](app/MoonShine/Layouts/AdminLayout.php:11).
- Command baru atau ubah signature di [`GenerateKataDoa.php`](app/Console/Commands/GenerateKataDoa.php:13) atau [`ImportDuaDhikr.php`](app/Console/Commands/ImportDuaDhikr.php:14).
- View baru atau ubah perilaku UI di [`beranda.blade.php`](resources/views/beranda.blade.php:1), [`show.blade.php`](resources/views/doa/show.blade.php:1), [`tersimpan.blade.php`](resources/views/doa/tersimpan.blade.php:1), [`kartu-doa.blade.php`](resources/views/components/kartu-doa.blade.php:1), [`app.blade.php`](resources/views/layouts/app.blade.php:1), [`login.blade.php`](resources/views/auth/login.blade.php:1), [`register.blade.php`](resources/views/auth/register.blade.php:1).
- Migrasi baru, kolom baru, enum status baru, atau akun admin baru seperti [`2026_09_23_000001_create_tabel_doa.php`](database/migrations/2026_09_23_000001_create_tabel_doa.php:11) dan [`2026_10_07_000001_create_moonshine_admin_user.php`](database/migrations/2026_10_07_000001_create_moonshine_admin_user.php:12).
- Perubahan peran, guard, middleware, throttle, atau aturan validasi.

Jika tidak ada pemicu di atas, catat alasan skip dan lanjut tanpa mengubah [`FITUR.md`](FITUR.md).

## Ground Rules

- Konsistensi dulu. Ikuti struktur, nomor bagian, dan gaya tabel yang sudah ada di [`FITUR.md`](FITUR.md). Jangan menata ulang dokumen tanpa diminta.
- Dokumentasikan realitas, bukan rencana. Hanya tulis perilaku yang benar-benar ada di kode dan terverifikasi via baca file.
- Satu sumber satu bagian. Setiap perubahan kode harus terpetakan ke minimal satu baris tabel atau satu bullet di [`FITUR.md`](FITUR.md).
- Tautan wajib hidup. Setiap referensi file atau simbol memakai format tautan klik seperti [`BerandaController::index()`](app/Http/Controllers/BerandaController.php:11). Baris wajib untuk simbol, opsional untuk nama file.
- Jangan hapus riwayat. Perbarui angka, matriks, dan contoh; jangan menghapus fitur lama kecuali kodenya benar-benar dihapus.

## Alur Kerja

### 1. Inventarisasi perubahan

1. Jalankan `git status --short` dan `git diff --name-only HEAD` untuk daftar file berubah.
2. Untuk file belum di-commit, bandingkan dengan isi kerja saat ini via baca langsung.
3. Tandai setiap file berubah sebagai pemicu atau bukan sesuai daftar di atas.
4. Jika tidak ada pemicu, berhenti dan laporkan skip.

### 2. Petakan ke bagian dokumen

Gunakan tabel pemetaan ini:

| Sumber berubah | Bagian [`FITUR.md`](FITUR.md) yang wajib dicek |
|---|---|
| [`routes/web.php`](routes/web.php:9) | Daftar Peran, Matriks Hak Akses, nomor bagian fitur terkait |
| [`BerandaController.php`](app/Http/Controllers/BerandaController.php:11), [`beranda.blade.php`](resources/views/beranda.blade.php:1) | Bagian 1 Beranda, Pencarian, Filter |
| [`DoaController.php`](app/Http/Controllers/DoaController.php:11), [`show.blade.php`](resources/views/doa/show.blade.php:1) | Bagian 2 Detail, Bagian 3 Doa Acak |
| [`AuthController.php`](app/Http/Controllers/AuthController.php:12), [`login.blade.php`](resources/views/auth/login.blade.php:1), [`register.blade.php`](resources/views/auth/register.blade.php:1) | Bagian 4 Autentikasi |
| [`DoaTersimpanController.php`](app/Http/Controllers/DoaTersimpanController.php:10), [`tersimpan.blade.php`](resources/views/doa/tersimpan.blade.php:1), [`Pengguna.php`](app/Models/Pengguna.php:8) | Bagian 6 Simpan, 7 Daftar Tersimpan, 8 Hapus |
| [`app.blade.php`](resources/views/layouts/app.blade.php:1) | Bagian 5 Layout, Navigasi, Tema |
| [`DoaResource.php`](app/MoonShine/Resources/DoaResource.php:26) | Bagian 9 Kelola Doa |
| [`KategoriResource.php`](app/MoonShine/Resources/KategoriResource.php:21) | Bagian 10 Kelola Kategori |
| [`KataDoaResource.php`](app/MoonShine/Resources/KataDoaResource.php:19), [`KataDoa.php`](app/Models/KataDoa.php:9) | Bagian 11 Review dan Alur Status |
| [`PengaturanHalaman.php`](app/MoonShine/Pages/PengaturanHalaman.php:27), [`Pengaturan.php`](app/Models/Pengaturan.php:8) | Bagian 12 Pengaturan |
| [`PenggunaResource.php`](app/MoonShine/Resources/PenggunaResource.php:18) | Bagian 13 Lihat Pengguna |
| [`ImportDuaDhikr.php`](app/Console/Commands/ImportDuaDhikr.php:14) | Bagian 14 Impor |
| [`GenerateKataDoa.php`](app/Console/Commands/GenerateKataDoa.php:13) | Bagian 15 Generate AI |
| Migrasi di [`database/migrations`](database/migrations/2026_09_23_000001_create_tabel_doa.php:11) | Alur Data, Batasan, skema tabel |
| [`MoonShineServiceProvider.php`](app/Providers/MoonShineServiceProvider.php:23), [`ResourceDasar.php`](app/MoonShine/Resources/ResourceDasar.php:18) | Pembuka Bagian 9 sampai 13 |

Detail checklist per dimensi ada di [`checklist.md`](references/checklist.md).

### 3. Perbarui dokumen

1. Baca bagian [`FITUR.md`](FITUR.md) yang terpetakan, jangan menebak isinya.
2. Perbarui dengan urutan ini:
   - Tabel Daftar Peran bila peran, identitas teknis, cara masuk, atau cakupan berubah.
   - Tabel Matriks Hak Akses bila URL, izin Tamu, Pengguna, Administrator, atau Operator CLI berubah. Tambah baris baru untuk rute baru.
   - Detail bernomor Bagian 1 sampai 15: rute, controller, view, query, paginasi, validasi, flash, perilaku kosong.
   - Alur Data dan Status Verifikasi bila enum `diterjemahkan_ai` atau `terverifikasi`, scope [`scopeTerverifikasi()`](app/Models/Doa.php:38), atau relasi [`kataDoa()`](app/Models/Doa.php:27) berubah.
   - Batasan dan Keamanan bila throttle, `simpanTujuan()`, slug `acak`, token disamarkan, atau aksi read-only berubah.
3. Tambah nomor bagian baru hanya untuk fitur yang benar-benar baru. Geser nomor hanya bila tidak bisa dihindari, lalu perbaiki semua referensi silang.
4. Jaga tautan memakai format [`Nama`](path:line). Contoh pola: [`doa.show`](routes/web.php:13), [`Doa::scopeTerverifikasi()`](app/Models/Doa.php:38).

### 4. Verifikasi

1. Pastikan setiap file pemicu dari Langkah 1 muncul minimal sekali di diff [`FITUR.md`](FITUR.md) atau tercatat sebagai tanpa dampak dokumen dengan alasan jelas.
2. Buka setiap tautan yang ditambah atau diubah, pastikan path dan nomor baris valid.
3. Jalankan `php artisan test --filter=` untuk area terkait bila tersedia, atau catat bila dilewati.
4. Baca ulang diff [`FITUR.md`](FITUR.md) dari atas ke bawah, pastikan tabel markdown sejajar dan tidak ada baris yatim.

## Kriteria Selesai

- Semua pemicu terpetakan ke [`FITUR.md`](FITUR.md) atau tercatat skip beralasan.
- Daftar Peran dan Matriks Hak Akses cocok dengan [`routes/web.php`](routes/web.php:9) dan guard aktual.
- Tidak ada tautan mati, tidak ada nomor bagian ganda, tidak ada fitur kode tanpa dokumentasi.
- Laporan akhir menyebut file kode yang berubah dan bagian dokumen yang diperbarui.

## Contoh Laporan

> Ubah [`DoaController.php`](app/Http/Controllers/DoaController.php:28) tambah pengecualian kategori: perbarui Bagian 3 Doa Acak dan baris Doa acak di Matriks. Tidak ada perubahan peran.
