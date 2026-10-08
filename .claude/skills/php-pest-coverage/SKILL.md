---
name: php-pest-coverage
description: "Use whenever adding, modifying, reviewing, or auditing PHP files in this Laravel project. Require meaningful Pest test coverage for every first-party PHP source file and run focused Pest tests."
---

# PHP Pest Coverage

## Tujuan

Pastikan setiap file PHP first-party memiliki tes Pest yang membuktikan perilakunya. Terapkan saat menambah atau mengubah PHP, memperbaiki bug, refactor, maupun mengaudit cakupan tes yang sudah ada.

## Cakupan File

- Inventarisasi file dengan `rg --files -g '*.php'`, lalu kecualikan `vendor/`, `public/vendor/`, `storage/`, `bootstrap/cache/`, dan file hasil generate atau dependensi pihak ketiga lainnya.
- Cakup seluruh kode PHP first-party, termasuk `app/`, `routes/`, `database/`, `config/`, `bootstrap/`, serta entry point seperti `artisan` dan `public/index.php`.
- File di `tests/` adalah bukti cakupan dan tidak perlu dites oleh tes lain.
- Untuk setiap file sumber dalam inventaris, catat pasangan `file PHP -> tes Pest` selama pekerjaan, termasuk file yang tidak diubah. Satu tes boleh mencakup beberapa file bila benar-benar menjalankan dan memverifikasi kontrak gabungannya.
- Jika file belum memiliki cakupan, tambahkan tes yang bermakna sebelum menyelesaikan pekerjaan. Jangan tinggalkan file first-party yang ada atau yang berubah tanpa pasangan tes.

## Alur Kerja

1. Baca instruksi repo dan tes di area terkait. Periksa versi Pest yang dipasang serta konfigurasi tes sebelum menentukan sintaks atau perintah.
2. Buat inventaris lengkap file PHP first-party sesuai cakupan di atas pada setiap pekerjaan PHP. Periksa pasangan tes untuk setiap file dalam inventaris, bukan hanya file yang diubah, lalu tambahkan tes untuk celah cakupan.
3. Pilih tes berdasarkan perilaku yang dapat diamati: request untuk controller dan route, perilaku domain untuk model atau service, skema/hasil untuk migration, serta hasil yang relevan untuk factory, seeder, konfigurasi, dan command. Tes integrasi boleh mencakup beberapa file yang bekerja sebagai satu alur.
4. Tulis atau perbarui tes menggunakan gaya Pest (`it()`/`test()` dan `expect()`). Suite ini 100% Pest; jangan buat file PHPUnit-style baru karena mode `--tia` menolaknya.
5. Tes harus gagal bila perilaku yang dimaksud rusak. Hindari tes tautologis seperti hanya memeriksa file ada, class dapat dimuat, atau menyalin implementasi ke assertion.
6. Jalankan tes Pest paling sempit yang mencakup perubahan, dengan nama file atau `--filter`. Jangan jalankan seluruh suite lokal kecuali diminta secara eksplisit.
7. Wajib jalankan Pest dengan flag `--parallel` atau `--tia` (proyek ini memakai Pest v5 + ParaTest, lihat `.ai/rules/pest.md`). Full suite: `vendor/bin/pest --parallel`. Iterasi cepat: `vendor/bin/pest --tia`. Gabungan tercepat: `vendor/bin/pest --parallel --tia`. Terfokus namun tetap paralel: `vendor/bin/pest --parallel tests/Feature/SomeTest.php --filter='nama test'`. Jangan menjalankan test tanpa salah satu flag tersebut.
8. Jika kode PHP produksi berubah, jalankan formatter PHP yang diwajibkan repo. Laporkan file PHP yang dicakup, pasangan tesnya, perintah validasi, dan kegagalan atau batasan yang tersisa.

## Kriteria Selesai

- Setiap file PHP first-party dalam cakupan memiliki pasangan tes Pest yang relevan, termasuk file yang tidak diubah dalam pekerjaan saat ini.
- Semua file PHP yang ditambah atau diubah tercakup oleh tes yang dijalankan.
- Tes terfokus lolos, atau kegagalannya dijelaskan dengan jelas dan belum diklaim selesai.