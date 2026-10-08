# Pest 5 — Cara Membuat Test dan Cara Menjalankannya

Aturan ini berlaku untuk `tests/**` dan semua kode first-party yang diuji (`app/**`, `routes/**`, `database/**`, `config/**`).

Stack terpasang: Pest `5.2.1` + ParaTest `7.25.0` (transitif via `pestphp/pest`). Konfigurasi: [`tests/Pest.php`](tests/Pest.php:1), suite di [`phpunit.xml`](phpunit.xml:1), branch default TIA `main`.

## 1. Membuat test baru — wajib gaya Pest

- Buat via Artisan dengan flag `--pest`: `php artisan make:test --pest NamaFiturTest` untuk Feature, tambah `--unit` untuk Unit.
- Jangan sertakan direktori suite di nama: `SomeFeatureTest`, bukan `Feature/SomeFeatureTest`.
- Test baru wajib gaya Pest (`it()`/`test()` + `expect()`), bukan class PHPUnit — suite ini 100% Pest dan mode `--tia` menolak class PHPUnit (`Tia mode requires Pest tests`).
- Setiap file Feature terikat ke [`Tests\TestCase`](tests/TestCase.php:1) lewat [`tests/Pest.php`](tests/Pest.php:20) dan memakai `uses(RefreshDatabase::class)` per-file (bukan global, bukan `uses(TestCase::class, ...)`) — pola global ganda memicu `TestCaseAlreadyInUse` dan transaksi ganda saat run paralel. Contoh benar ada di [`tests/Feature/DoaTest.php`](tests/Feature/DoaTest.php:1).
- Tulis Bahasa Indonesia untuk nama test perilaku domain, konsisten dengan suite yang ada (`it('hanya memilih doa ...')`).
- Ikuti skill `testing-best-practices` untuk coverage, naming, assertion, dan isolasi; ikuti `php-pest-coverage` untuk pemetaan file → test.

## 2. Menjalankan test — wajib `--parallel` atau `--tia`

JANGAN pernah menjalankan `vendor/bin/pest` / `php artisan test` / `composer run test` tanpa flag paralel. Pilih salah satu:

| Kebutuhan | Perintah baku |
| --- | --- |
| Full suite / CI / verifikasi akhir | `vendor/bin/pest --parallel` |
| Iterasi cepat saat ngoding (hanya test terdampak) | `vendor/bin/pest --tia` |
| Test terfokus + tetap paralel | `vendor/bin/pest --parallel tests/Feature/NamaTest.php --filter='nama test'` |
| Via Artisan | `php artisan test --parallel` (`composer run test` sudah mengarah ke sini) |

Catatan penting:

- Suite ini sudah 100% gaya Pest, sehingga `--parallel`, `--tia`, maupun gabungan `--parallel --tia` semuanya berjalan. Jangan menambah file PHPUnit-style baru (class `extends TestCase`) — mode `--tia` menolaknya dengan `Tia mode requires Pest tests`.
- `--tia` butuh `pest()->tia()->defaultBranch('main')` di [`tests/Pest.php`](tests/Pest.php:21). Jangan hapus baris itu. Jika remote/branch berubah, sesuaikan nilainya.
- Test harus paralel-aman: buat sendiri record yang dibaca, jangan bergantung urutan run, jangan berbagi file/cache-key/queue antar test (lihat `testing-best-practices/rules/performance.md`).
- Migrasi: jangan loop manual `glob(database/migrations/*.php)` + `$migration->up()` — itu melewatkan migrasi vendor (MoonShine) dan gagal dengan `no such table: moonshine_user_roles`. Gunakan `Artisan::call('migrate', ...)` / `migrate:reset` seperti di [`tests/Feature/ProjectSourceCoverageTest.php`](tests/Feature/ProjectSourceCoverageTest.php:42).

## 3. Setelah ubah PHP

- Jalankan test tersempit yang mencakup perubahan dengan `--parallel` (file atau `--filter`), ulangi tiap perubahan pada test.
- Jalankan formatter: `vendor/bin/pint --dirty --format agent`.
- Full `vendor/bin/pest --parallel` wajib hijau sebelum selesai (saat ini: 58 passed).
