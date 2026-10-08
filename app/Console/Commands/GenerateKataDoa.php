<?php

namespace App\Console\Commands;

use App\Models\Doa;
use App\Services\GeneratorKataDoa;
use Illuminate\Console\Command;

class GenerateKataDoa extends Command
{
    protected $signature = 'generate:kata-doa
        {doa_id? : ID doa tertentu}
        {--semua : Proses semua doa yang belum punya arti per kata}
        {--ulang : Buat ulang meski sudah ada (HAPUS semua kata doa tsb, termasuk yang terverifikasi)}';

    protected $description = 'Buat draf arti per kata lewat API AI (status: diterjemahkan_ai)';

    public function handle(GeneratorKataDoa $generator): int
    {
        if (! $generator->siap()) {
            $this->error($generator->pesanBelumSiap());

            return self::FAILURE;
        }

        $query = Doa::query();

        if ($id = $this->argument('doa_id')) {
            $query->whereKey($id);
        } elseif ($this->option('semua')) {
            if (! $this->option('ulang')) {
                $query->whereDoesntHave('kataDoa');
            }
        } else {
            $this->error('Berikan {doa_id} atau gunakan --semua.');

            return self::FAILURE;
        }

        $daftar = $query->get();
        if ($daftar->isEmpty()) {
            $this->info('Tidak ada doa yang perlu diproses.');

            return self::SUCCESS;
        }

        foreach ($daftar as $doa) {
            if ($doa->kataDoa()->exists() && ! $this->option('ulang')) {
                $this->warn("Lewati #{$doa->id} {$doa->judul}: sudah punya kata (pakai --ulang untuk membuat ulang).");

                continue;
            }

            $this->line("Memproses #{$doa->id} {$doa->judul} ...");

            try {
                $jumlah = $generator->generate($doa);
            } catch (\Throwable $e) {
                $this->error("  Gagal: {$e->getMessage()}");

                continue;
            }

            $this->info("  {$jumlah} kata disimpan (status: diterjemahkan_ai).");
        }

        return self::SUCCESS;
    }
}
