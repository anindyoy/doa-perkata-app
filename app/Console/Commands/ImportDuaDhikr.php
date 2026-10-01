<?php

namespace App\Console\Commands;

use App\Models\Doa;
use App\Models\Kategori;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportDuaDhikr extends Command
{
    protected $signature = 'import:dua-dhikr
        {kategori=daily-dua : Nama folder dataset (daily-dua, selected-dua, morning-dhikr, evening-dhikr, dhikr-after-salah)}
        {--path= : Folder lokal hasil clone fitrahive/dua-dhikr (opsional, default: unduh dari GitHub)}
        {--generate : Langsung generate arti per kata via AI setelah import}';

    protected $description = 'Import doa dari dataset fitrahive/dua-dhikr (id.json) ke tabel doa (upsert)';

    private const NAMA_KATEGORI = [
        'daily-dua' => 'Doa Harian',
        'selected-dua' => 'Doa Pilihan',
        'morning-dhikr' => 'Dzikir Pagi',
        'evening-dhikr' => 'Dzikir Petang',
        'dhikr-after-salah' => 'Dzikir Setelah Shalat',
    ];

    public function handle(): int
    {
        $folder = $this->argument('kategori');
        $data = $this->ambilData($folder);

        if (! is_array($data) || $data === []) {
            $this->error('Data kosong atau format JSON tidak sesuai.');

            return self::FAILURE;
        }

        $kategori = Kategori::firstOrCreate(
            ['slug' => $folder],
            [
                'nama' => self::NAMA_KATEGORI[$folder] ?? Str::headline($folder),
                'urutan' => (int) Kategori::max('urutan') + 1,
            ]
        );

        $hitung = [];
        $baru = $diperbarui = 0;
        $idBaru = [];

        foreach ($data as $i => $item) {
            if (empty($item['title']) || empty($item['arabic'])) {
                continue;
            }

            $dasar = Str::slug($item['title']) ?: 'doa';
            $n = $hitung[$dasar] = ($hitung[$dasar] ?? 0) + 1;
            $akhiran = $n > 1 ? "-{$n}" : '';
            $idEksternal = "{$folder}:{$dasar}{$akhiran}";

            $doa = Doa::firstOrNew(['id_eksternal' => $idEksternal]);
            $sudahAda = $doa->exists;

            if (! $sudahAda) {
                $doa->slug = $this->slugUnik($dasar.$akhiran, $folder);
            }

            $doa->fill([
                'kategori_id' => $doa->kategori_id ?: $kategori->id, // hormati kategori yang diubah admin
                'judul' => $item['title'],
                'teks_arab' => $item['arabic'],
                'transliterasi' => $item['latin'] ?? null,
                'terjemahan' => $item['translation'] ?? '',
                'catatan' => $item['notes'] ?? null,
                'referensi_sumber' => $item['source'] ?? null,
                'urutan' => $doa->urutan ?: $i + 1,
            ])->save();

            $sudahAda ? $diperbarui++ : $baru++;
            $idBaru[] = $doa->id;
        }

        $this->info("Selesai: {$baru} doa baru, {$diperbarui} diperbarui (kategori: {$kategori->nama}).");

        if ($this->option('generate')) {
            foreach ($idBaru as $id) {
                $this->call('generate:kata-doa', ['doa_id' => $id]);
            }
        }

        return self::SUCCESS;
    }

    private function ambilData(string $folder): mixed
    {
        if ($path = $this->option('path')) {
            $berkas = rtrim($path, '/')."/data/dua-dhikr/{$folder}/id.json";
            if (! File::exists($berkas)) {
                $this->error("Berkas tidak ditemukan: {$berkas}");

                return null;
            }

            return json_decode(File::get($berkas), true);
        }

        $url = "https://raw.githubusercontent.com/fitrahive/dua-dhikr/main/data/dua-dhikr/{$folder}/id.json";
        $this->line("Mengunduh {$url}");

        return Http::timeout(30)->get($url)->throw()->json();
    }

    private function slugUnik(string $slug, string $folder): string
    {
        if ($slug === 'acak') { // bentrok dengan route /doa/acak
            $slug = 'doa-acak';
        }

        $calon = $slug;
        $k = 1;
        while (Doa::where('slug', $calon)->exists()) {
            $calon = $k === 1 ? "{$slug}-{$folder}" : "{$slug}-{$folder}-{$k}";
            $k++;
        }

        return $calon;
    }
}
