# Fitur Aplikasi Doa Perkata dan Peran Pengguna

Aplikasi Doa Perkata membantu memahami doa harian lewat arti setiap kata, bukan hanya terjemahan utuh. Dokumen ini menjelaskan semua fitur utama dan siapa yang boleh menggunakannya.

Dokumen terkait: [`README.md`](README.md) untuk instalasi, impor data, dan generate arti per kata.

## Daftar Peran

| Peran | Identitas Teknis | Cara Masuk | Cakupan |
|---|---|---|---|
| Tamu | Pengunjung tanpa sesi login, dibatasi middleware [`guest`](routes/web.php:15) untuk halaman auth dan [`auth`](routes/web.php:22) untuk area tersimpan | Tanpa login | Baca beranda, cari, filter kategori, baca detail doa, doa acak, daftar, masuk |
| Pengguna | Model [`Pengguna.php`](app/Models/Pengguna.php) pada tabel pengguna, guard web sesi, kolom kata sandi ter-hash | Form [`login.blade.php`](resources/views/auth/login.blade.php) di rute masuk dan [`register.blade.php`](resources/views/auth/register.blade.php) di rute daftar | Semua hak Tamu plus simpan, lihat daftar tersimpan, hapus simpanan, keluar |
| Administrator | Akun [`moonshine_users`](database/migrations/2026_10_07_000001_create_moonshine_admin_user.php:12) dengan role Admin bawaan MoonShine, default admin@mail.com, password dari env MOONSHINE_ADMIN_PASSWORD | Panel admin MoonShine di /admin | Kelola Doa, Kategori, Kata Doa, Pengaturan, lihat Pengguna |
| Operator CLI | Maintainer dengan akses terminal server | Perintah artisan di server | Impor dataset, generate draf AI per kata |

Tidak ada role editor terpisah. Tidak ada pendaftaran admin dari halaman publik. Akun admin dibuat lewat migrasi [`2026_10_07_000001_create_moonshine_admin_user.php`](database/migrations/2026_10_07_000001_create_moonshine_admin_user.php:12).

## Matriks Hak Akses

| Fitur / URL | Tamu | Pengguna | Administrator | Operator CLI |
|---|---|---|---|---|
| Beranda daftar doa / | Ya | Ya | Ya | - |
| Cari dan filter kategori | Ya | Ya | Ya | - |
| Detail doa /doa/slug | Ya | Ya | Ya | - |
| Doa acak /doa/acak | Ya | Ya | Ya | - |
| Toggle tampil arti per kata | Ya | Ya | Ya | - |
| Label draf AI vs terverifikasi | Ya, hanya lihat | Ya, hanya lihat | Ya, ubah status | - |
| Daftar /masuk, /daftar | Ya | Tidak, sudah login diarahkan | - | - |
| Simpan doa, daftar /tersimpan, hapus simpanan | Tidak, diarahkan login dengan param kembali | Ya, milik sendiri saja | Tidak via panel, hanya via web sebagai pengguna bila punya akun pengguna | - |
| CRUD Doa di panel admin | Tidak | Tidak | Ya | Tidak, tapi bisa impor via CLI |
| CRUD Kategori di panel admin | Tidak | Tidak | Ya | Tidak |
| Review Kata Doa di panel admin | Tidak | Tidak | Ya | Tidak |
| Ubah Pengaturan di panel admin | Tidak | Tidak | Ya | Tidak, tapi dibaca oleh CLI |
| Lihat Pengguna di panel admin | Tidak | Tidak, hanya data sendiri | Ya, read-only | - |
| Impor dan generate AI via CLI | Tidak | Tidak | Tidak langsung, harus via terminal | Ya |

## Fitur Publik, Tamu dan Pengguna

### 1. Beranda, Pencarian, dan Filter Kategori

- Rute [`beranda`](routes/web.php:9) ke [`BerandaController::index()`](app/Http/Controllers/BerandaController.php:11).
- Tampilan [`beranda.blade.php`](resources/views/beranda.blade.php) dengan kartu [`kartu-doa.blade.php`](resources/views/components/kartu-doa.blade.php).
- Hanya doa yang punya minimal satu arti per kata yang ditampilkan, via scope [`Doa::scopeAdaArtiPerKata()`](app/Models/Doa.php:45). Doa tanpa relasi [`Doa::kataDoa()`](app/Models/Doa.php:27) disembunyikan dari daftar, pencarian, dan filter kategori.
- Cari judul, teks Arab, atau transliterasi lewat param cari. Contoh: beranda dengan cari bangun tidur.
- Filter kategori lewat param kategori berisi slug. Tombol Semua plus satu tombol per kategori dari model [`Kategori.php`](app/Models/Kategori.php).
- Urut berdasar kolom urutan lalu id. Paginasi 12 per halaman dengan query string dipertahankan.
- Keadaan kosong menampilkan pesan dan tautan lihat semua doa.
- Dapat diakses Tamu dan Pengguna tanpa login.

### 2. Detail Doa dan Arti Per Kata

- Rute [`doa.show`](routes/web.php:13) ke [`DoaController::show()`](app/Http/Controllers/DoaController.php:11) dengan binding slug.
- Tampilan [`show.blade.php`](resources/views/doa/show.blade.php).
- Isi halaman:
  - Judul, nama kategori sebagai tautan filter, teks Arab RTL, transliterasi latin italic bila ada, catatan, terjemahan lengkap, referensi sumber.
  - Daftar kata dari relasi [`Doa::kataDoa()`](app/Models/Doa.php:27) urut kolom urutan. Tiap kartu kata menampilkan kata Arab, transliterasi kata, arti kata, dan label draf AI bila status bukan terverifikasi.
  - Toggle Tampilkan Arti per Kata berbasis Alpine, default menyala.
  - Peringatan bila ada kata belum terverifikasi, dihitung lewat flag adaBelumTerverifikasi.
  - Tombol simpan berbeda untuk Tamu, Pengguna belum menyimpan, dan Pengguna sudah menyimpan.
- Dapat diakses Tamu dan Pengguna. Tamu yang menekan simpan diarahkan ke halaman masuk dengan param kembali ke path doa.

### 3. Doa Acak

- Rute [`doa.acak`](routes/web.php:12) ke [`DoaController::acak()`](app/Http/Controllers/DoaController.php:28). Didefinisikan sebelum rute slug agar kata acak tidak dianggap slug.
- Tombol Doa Acak ada di navigasi [`app.blade.php`](resources/views/layouts/app.blade.php:39).
- Perilaku:
  - Bila pengaturan [`doa_acak_hanya_terverifikasi`](database/migrations/2026_09_23_000001_create_tabel_doa.php:64) aktif, query dibatasi scope [`Doa::scopeTerverifikasi()`](app/Models/Doa.php:38) yaitu punya minimal satu kata dan tidak punya kata berstatus selain terverifikasi.
  - Riwayat 5 id terakhir disimpan di sesi kunci doa_acak_riwayat agar tidak langsung berulang. Bila semua eligible sudah di riwayat, riwayat diabaikan.
  - Redirect langsung ke detail doa acak. Bila kosong, kembali ke beranda dengan pesan info.
- Dapat diakses Tamu dan Pengguna.

### 4. Autentikasi Pengguna, Daftar, Masuk, Keluar

- Controller [`AuthController.php`](app/Http/Controllers/AuthController.php).
- Rute tamu di grup [`guest`](routes/web.php:15): form login [`AuthController::formLogin()`](app/Http/Controllers/AuthController.php:12), proses login [`AuthController::login()`](app/Http/Controllers/AuthController.php:19) dengan throttle 6 percobaan per menit, form register [`AuthController::formRegister()`](app/Http/Controllers/AuthController.php:38), proses register [`AuthController::register()`](app/Http/Controllers/AuthController.php:45) dengan throttle sama.
- Rute auth di grup [`auth`](routes/web.php:22): proses logout [`AuthController::logout()`](app/Http/Controllers/AuthController.php:61).
- Detail:
  - Login memakai email dan kata_sandi, opsi ingat saya, pesan Email atau kata sandi salah bila gagal, regenerasi sesi bila sukses, redirect ke intended atau beranda.
  - Register memakai nama, email unik di tabel pengguna, kata_sandi minimal 8 dengan konfirmasi, auto login setelah buat akun. Model [`Pengguna.php`](app/Models/Pengguna.php:19) meng-hash kata_sandi via casts hashed, menyembunyikan kata_sandi dan token_ingat.
  - Logout membatalkan sesi dan regenerasi token, kembali ke beranda.
  - Dukungan kembali setelah login: param kembali hanya path lokal diawali satu garis miring, disimpan ke url.intended oleh [`AuthController::simpanTujuan()`](app/Http/Controllers/AuthController.php:72).
  - Hanya Tamu yang bisa buka halaman masuk dan daftar. Hanya Pengguna yang bisa keluar.
- Tampilan: [`login.blade.php`](resources/views/auth/login.blade.php), [`register.blade.php`](resources/views/auth/register.blade.php).

### 5. Layout, Navigasi, dan Tema

- Layout [`app.blade.php`](resources/views/layouts/app.blade.php).
- Menu: Semua Doa, Doa Acak, Doa Tersimpan dan Keluar plus nama bila login, atau Masuk dan Daftar bila tamu.
- Toggle terang gelap disimpan di localStorage kunci tema, hormat preferensi sistem.
- Flash info sesi ditampilkan di atas konten.
- Footer mencantumkan sumber dataset fitrahive dua-dhikr dan catatan bahwa label draf AI belum dicek manusia.

## Fitur Khusus Pengguna Login

### 6. Simpan Doa

- Rute [`doa.simpan`](routes/web.php:25) POST ke [`DoaTersimpanController::simpan()`](app/Http/Controllers/DoaTersimpanController.php:17).
- Memakai relasi [`Pengguna::doaTersimpan()`](app/Models/Pengguna.php:39) dengan syncWithoutDetaching sehingga tidak dobel, didukung unique pengguna_id plus doa_id di tabel doa_tersimpan.
- Flash Doa disimpan dan kembali ke halaman sebelumnya.
- Hanya Pengguna. Tamu diarahkan login.

### 7. Daftar Doa Tersimpan

- Rute [`tersimpan`](routes/web.php:24) GET ke [`DoaTersimpanController::index()`](app/Http/Controllers/DoaTersimpanController.php:10).
- Tampilan [`tersimpan.blade.php`](resources/views/doa/tersimpan.blade.php).
- Query milik user login saja, eager kategori, paginasi 12, urut disimpan_pada terbaru via pivot.
- Keadaan kosong mengajak jelajahi doa.
- Hanya Pengguna, data terisolasi per akun.

### 8. Hapus Doa Tersimpan

- Rute [`doa.hapus`](routes/web.php:26) DELETE ke [`DoaTersimpanController::hapus()`](app/Http/Controllers/DoaTersimpanController.php:25).
- Aksi detach satu id, flash Doa dihapus dari daftar tersimpan.
- Tersedia dua tempat: tombol Tersimpan di halaman detail dan tautan Hapus dari daftar di halaman tersimpan.
- Hanya pemilik simpanan, yakni user login yang menyimpan.

## Fitur Khusus Administrator, Panel MoonShine

Panel didaftarkan di [`MoonShineServiceProvider.php`](app/Providers/MoonShineServiceProvider.php:23) dengan layout [`AdminLayout.php`](app/MoonShine/Layouts/AdminLayout.php) dan basis CRUD [`ResourceDasar.php`](app/MoonShine/Resources/ResourceDasar.php:18). Semua form memakai halaman bersama [`IndexHalaman.php`](app/MoonShine/Pages/IndexHalaman.php), [`FormHalaman.php`](app/MoonShine/Pages/FormHalaman.php), [`DetailHalaman.php`](app/MoonShine/Pages/DetailHalaman.php).

### 9. Kelola Doa

- Resource [`DoaResource.php`](app/MoonShine/Resources/DoaResource.php).
- Index: ID, judul sortable, kategori relasi, urutan sortable. Pencarian di [`DoaResource::search()`](app/MoonShine/Resources/DoaResource.php:102) mencakup judul, teks_arab, transliterasi. Sort default urutan menaik.
- Form:
  - Box utama: judul wajib, slug auto dari judul wajib dan unik kecuali kata acak yang dilarang, kategori opsional, urutan default 0, teks Arab wajib, transliterasi opsional, terjemahan wajib, catatan, referensi sumber.
  - Box Arti per Kata: repeater relasi kataDoa ke [`KataDoaResource.php`](app/MoonShine/Resources/KataDoaResource.php) dengan field urutan, kata Arab wajib, transliterasi, arti wajib, status pilihan diterjemahkan_ai default atau terverifikasi dengan badge kuning hijau. Bisa tambah dan hapus.
- Validasi di [`DoaResource::aturan()`](app/MoonShine/Resources/DoaResource.php:87): judul, slug unik, kategori_id harus ada, teks_arab, terjemahan, urutan integer min 0.
- Hanya Administrator. Pengguna publik tidak bisa tambah, ubah, hapus doa.

### 10. Kelola Kategori

- Resource [`KategoriResource.php`](app/MoonShine/Resources/KategoriResource.php).
- Index: ID, nama sortable, slug, urutan sortable, jumlah doa dari withCount. Pencarian nama dan slug.
- Form: nama wajib, slug auto dari nama wajib unik, urutan default 0.
- Validasi di [`KategoriResource::aturan()`](app/MoonShine/Resources/KategoriResource.php:59).
- Mengubah kategori tidak merusak impor ulang karena impor menghormati kategori yang diubah admin.
- Hanya Administrator.

### 11. Review Kata Doa dan Verifikasi

- Resource [`KataDoaResource.php`](app/MoonShine/Resources/KataDoaResource.php) berjudul Kata Doa Review.
- Index: ID, relasi doa tampil judul, kata Arab, arti, urutan sortable, status dengan badge. Filter status di [`KataDoaResource::filters()`](app/MoonShine/Resources/KataDoaResource.php:61). Pencarian kata_arab dan arti_kata.
- Form: relasi doa wajib, urutan, kata Arab wajib, transliterasi, arti wajib, status default diterjemahkan_ai.
- Alur status:
  - diterjemahkan_ai berarti draf AI, tampil label draf AI di publik.
  - terverifikasi berarti sudah dicek manusia, tanpa label, dan dihitung sebagai eligible untuk doa acak bila pengaturan aktif.
- Model [`KataDoa.php`](app/Models/KataDoa.php) tanpa timestamps, fillable doa_id, kata_arab, transliterasi_kata, arti_kata, urutan, status.
- Hanya Administrator yang bisa verifikasi.

### 12. Kelola Pengaturan

- Resource [`PengaturanResource.php`](app/MoonShine/Resources/PengaturanResource.php).
- Mode khusus: hanya aksi UPDATE, tanpa tambah dan hapus karena baris dibuat lewat migrasi. Kunci readonly.
- Field dinamis berdasar kunci:
  - Kunci doa_acak_hanya_terverifikasi tampil sebagai select Aktif Nonaktif nilai 1 atau 0, dibaca oleh [`Pengaturan::aktif()`](app/Models/Pengaturan.php:24).
  - Kunci terjemahan_perkata_api_token tampil sebagai input password, nilainya disamarkan menjadi bintang lewat accessor nilai_tampil di [`Pengaturan.php`](app/Models/Pengaturan.php:31).
  - Kunci lain tampil sebagai teks biasa seperti terjemahan_perkata_api_url, terjemahan_perkata_model, terjemahan_perkata_max_tokens.
- Helper [`Pengaturan::get()`](app/Models/Pengaturan.php:17) dipakai CLI untuk URL, model, token, max tokens.
- Hanya Administrator.

### 13. Lihat Pengguna

- Resource [`PenggunaResource.php`](app/MoonShine/Resources/PenggunaResource.php).
- Mode read-only: hanya aksi VIEW.
- Index: ID, nama, email, tanggal terdaftar dari dibuat_pada format d M Y H:i. Pencarian nama dan email.
- Tidak ada ubah password, hapus, atau tambah dari panel.
- Hanya Administrator.

## Fitur Khusus Operator CLI

### 14. Impor Dataset Dua Dhikr

- Command [`ImportDuaDhikr.php`](app/Console/Commands/ImportDuaDhikr.php:14) dengan signature import dua-dhikr.
- Sumber: unduh id.json dari GitHub fitrahive dua-dhikr atau baca lokal via opsi path ke folder hasil clone.
- Kategori didukung: daily-dua ke Doa Harian, selected-dua ke Doa Pilihan, morning-dhikr ke Dzikir Pagi, evening-dhikr ke Dzikir Petang, dhikr-after-salah ke Dzikir Setelah Shalat. Nama lain memakai headline otomatis.
- Logika upsert berdasar id_eksternal format folder slug. Slug unik otomatis, kata acak dialihkan ke doa-acak agar tidak bentrok rute. Kategori yang sudah diubah admin tidak ditimpa. Urutan default nomor urut file.
- Opsi generate langsung memanggil generate kata-doa per id baru.
- Contoh pola pakai: impor daily-dua dari GitHub, impor via path lokal, impor plus generate. Lihat detail argumen di [`ImportDuaDhikr.php`](app/Console/Commands/ImportDuaDhikr.php:14).
- Peran Operator CLI. Biasanya dijalankan sekali saat database kosong atau saat tambah kategori.

### 15. Generate Arti Per Kata via AI

- Command [`GenerateKataDoa.php`](app/Console/Commands/GenerateKataDoa.php:13) dengan argumen doa_id dan opsi semua serta ulang.
- Prasyarat pengaturan URL API dan model di menu Pengaturan, token opsional, max tokens default 8000.
- Alur di [`GenerateKataDoa::handle()`](app/Console/Commands/GenerateKataDoa.php:32):
  - Satu doa bila diberi id, semua doa tanpa kata bila opsi semua, tolak bila tanpa argumen.
  - Lewati bila sudah punya kata kecuali opsi ulang yang menghapus semua kata termasuk terverifikasi dalam transaksi.
  - Minta AI via [`GenerateKataDoa::mintaAi()`](app/Console/Commands/GenerateKataDoa.php:96) dengan system prompt pecah kata Arab berharakat plus transliterasi dan arti konteks, balas JSON array saja, timeout 180 detik, validasi tiap item wajib kata_arab dan arti_kata.
  - Simpan dengan urutan mulai 1 dan status diterjemahkan_ai.
- Hasil selalu draf, wajib diverifikasi admin di panel sebelum dianggap final.
- Peran Operator CLI dengan konfigurasi milik Administrator.

## Alur Data dan Status Verifikasi

1. Operator impor doa tanpa kata atau dengan kata kosong.
2. Operator generate draf AI status diterjemahkan_ai.
3. Administrator buka Kata Doa Review, periksa kata_arab, transliterasi_kata, arti_kata, urutan, lalu ubah status ke terverifikasi.
4. Publik melihat arti plus label draf AI untuk yang belum terverifikasi. Doa dengan semua kata terverifikasi menjadi eligible doa acak bila pengaturan aktif, lewat relasi [`Doa::disimpanOleh()`](app/Models/Doa.php:32) untuk simpanan dan scope terverifikasi untuk acak.
5. Skema tabel ada di [`2026_09_23_000001_create_tabel_doa.php`](database/migrations/2026_09_23_000001_create_tabel_doa.php:11): tabel kategori, doa dengan id_eksternal unik dan slug unik, kata_doa dengan enum status dan index doa_id urutan, doa_tersimpan dengan unique pengguna_id doa_id, pengaturan dengan kunci unik.

## Batasan dan Keamanan per Peran

- Tamu tidak bisa akses rute auth tersimpan, akan diarahkan login.
- Pengguna hanya akses simpanan milik sendiri, tidak bisa lihat simpanan orang lain, tidak bisa akses panel admin.
- Password pengguna di-hash otomatis, kolom sensitif hidden. Login dan register dibatasi throttle.
- Param kembali divalidasi hanya path lokal untuk cegah open redirect.
- Slug acak dilarang untuk doa agar tidak menabrak rute doa acak.
- Token API disamarkan di daftar pengaturan.
- Pengguna panel read-only tidak bisa diubah, pengaturan tidak bisa tambah hapus, sehingga struktur kunci API aman.
