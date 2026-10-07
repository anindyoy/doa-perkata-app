# Checklist Sinkron FITUR.md

Gunakan checklist ini setelah Langkah 2 di [`SKILL.md`](../SKILL.md). Centang satu per satu; semua Ya atau N/A sebelum selesai.

## A. Rute dan Akses

- [ ] Setiap rute baru di [`web.php`](routes/web.php:9) sudah punya baris di Matriks Hak Akses
- [ ] Middleware [`guest`](routes/web.php:15) dan [`auth`](routes/web.php:22) cocok dengan kolom Tamu dan Pengguna
- [ ] Throttle `6,1` di rute masuk dan daftar masih akurat bila diubah
- [ ] Urutan `acak` sebelum `slug` masih didokumentasikan bila rute doa berubah
- [ ] Param `kembali` dan fungsi [`simpanTujuan()`](app/Http/Controllers/AuthController.php:72) cocok dengan Bagian 4

## B. Publik

- [ ] Param `cari` dan `kategori` di [`BerandaController.php`](app/Http/Controllers/BerandaController.php:11) cocok dengan Bagian 1
- [ ] Field pencarian judul, teks Arab, transliterasi masih benar
- [ ] Paginasi 12 dan `withQueryString()` masih benar
- [ ] Flag `adaBelumTerverifikasi` di [`DoaController.php`](app/Http/Controllers/DoaController.php:11) cocok dengan Bagian 2
- [ ] Toggle Alpine `tampilArti` dan label `draf AI` cocok dengan [`show.blade.php`](resources/views/doa/show.blade.php:5)
- [ ] Pengaturan [`doa_acak_hanya_terverifikasi`](database/migrations/2026_09_23_000001_create_tabel_doa.php:64) dan sesi `doa_acak_riwayat` cocok dengan Bagian 3
- [ ] Menu di [`app.blade.php`](resources/views/layouts/app.blade.php:23) cocok dengan Bagian 5

## C. Pengguna Login

- [ ] `syncWithoutDetaching` di [`DoaTersimpanController.php`](app/Http/Controllers/DoaTersimpanController.php:17) dan unique `pengguna_id` plus `doa_id` cocok dengan Bagian 6
- [ ] Eager `kategori`, paginasi 12, urut pivot `disimpan_pada` cocok dengan Bagian 7
- [ ] Aksi `detach` dan dua lokasi hapus cocok dengan Bagian 8
- [ ] Isolasi milik sendiri ditegaskan, tidak ada klaim akses silang

## D. Admin MoonShine

- [ ] Daftar resource di [`MoonShineServiceProvider.php`](app/Providers/MoonShineServiceProvider.php:23) cocok dengan pembuka Bagian 9 sampai 13
- [ ] Field index, form, search, validasi `aturan()` di tiap resource cocok dengan Bagian 9 sampai 13
- [ ] Status `diterjemahkan_ai` vs `terverifikasi` plus badge `SUCCESS` dan `WARNING` cocok dengan Bagian 11
- [ ] `activeActions` hanya `UPDATE` di Pengaturan dan hanya `VIEW` di Pengguna masih benar
- [ ] Masking `nilai_tampil` menjadi bintang untuk token cocok dengan Bagian 12

## E. CLI

- [ ] Signature `import:dua-dhikr`, argumen kategori, opsi `path` dan `generate` di [`ImportDuaDhikr.php`](app/Console/Commands/ImportDuaDhikr.php:14) cocok dengan Bagian 14
- [ ] Lima folder dataset dan nama kategori cocok
- [ ] Hormati kategori admin, slug unik, alih `acak` ke `doa-acak` masih benar
- [ ] Signature `generate:kata-doa`, argumen `doa_id`, opsi `semua` dan `ulang` cocok dengan Bagian 15
- [ ] Prasyarat pengaturan URL, model, token, max tokens serta system prompt JSON array masih benar

## F. Data dan Keamanan

- [ ] Skema di [`2026_09_23_000001_create_tabel_doa.php`](database/migrations/2026_09_23_000001_create_tabel_doa.php:11) cocok dengan Alur Data
- [ ] Akun di [`2026_10_07_000001_create_moonshine_admin_user.php`](database/migrations/2026_10_07_000001_create_moonshine_admin_user.php:12) cocok dengan Daftar Peran
- [ ] Relasi [`kataDoa()`](app/Models/Doa.php:27), [`disimpanOleh()`](app/Models/Doa.php:32), [`doaTersimpan()`](app/Models/Pengguna.php:39), scope [`scopeTerverifikasi()`](app/Models/Doa.php:38) masih benar
- [ ] Batasan slug `acak`, token disamarkan, throttle, validasi path lokal masih benar

## G. Format

- [ ] Semua referensi file memakai [`nama`](path:line)
- [ ] Semua referensi simbol memakai [`Kelas::metode()`](path:line) dengan baris
- [ ] Tidak ada nomor bagian ganda atau tautan mati
- [ ] Tabel Matriks sejajar dan contoh laporan diisi
