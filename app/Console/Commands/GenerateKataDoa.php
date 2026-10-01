<?php

namespace App\Console\Commands;

use App\Models\Doa;
use App\Models\Pengaturan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GenerateKataDoa extends Command
{
    protected $signature = 'generate:kata-doa
        {doa_id? : ID doa tertentu}
        {--semua : Proses semua doa yang belum punya arti per kata}
        {--ulang : Buat ulang meski sudah ada (HAPUS semua kata doa tsb, termasuk yang terverifikasi)}';

    protected $description = 'Buat draf arti per kata lewat API AI (status: diterjemahkan_ai)';

    private const SYSTEM = <<<'TXT'
Kamu adalah ahli bahasa Arab yang membantu aplikasi doa harian.
Pecah teks doa Arab menjadi kata per kata, sesuai urutan teks. Untuk tiap kata beri:
- kata_arab: kata persis seperti di teks (pertahankan harakat), tanpa mengubah/menambah kata
- transliterasi_kata: cara baca latin kata tersebut
- arti_kata: arti singkat dalam Bahasa Indonesia sesuai konteks doa (boleh menggabungkan partikel seperti و/ب/ل ke kata berikutnya jika memang melekat)
Abaikan tanda waqf/ayat yang berdiri sendiri. Jangan beri penjelasan lain.
Balas HANYA JSON array tanpa markdown, contoh:
[{"kata_arab":"الْحَمْدُ","transliterasi_kata":"al-ḥamdu","arti_kata":"segala puji"}]
TXT;

    public function handle(): int
    {
        if (! Pengaturan::get('terjemahan_perkata_api_url') || ! Pengaturan::get('terjemahan_perkata_model')) {
            $this->error('URL API dan model terjemahan per kata harus diatur di menu Pengaturan.');

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
                $kata = $this->mintaAi($doa->teks_arab);
            } catch (\Throwable $e) {
                $this->error("  Gagal: {$e->getMessage()}");

                continue;
            }

            DB::transaction(function () use ($doa, $kata) {
                $doa->kataDoa()->delete();
                foreach ($kata as $i => $k) {
                    $doa->kataDoa()->create([
                        'kata_arab' => $k['kata_arab'],
                        'transliterasi_kata' => $k['transliterasi_kata'] ?? null,
                        'arti_kata' => $k['arti_kata'],
                        'urutan' => $i + 1,
                        'status' => 'diterjemahkan_ai',
                    ]);
                }
            });

            $this->info('  '.count($kata).' kata disimpan (status: diterjemahkan_ai).');
        }

        return self::SUCCESS;
    }

    private function mintaAi(string $teksArab): array
    {
        $token = Pengaturan::get('terjemahan_perkata_api_token');
        $request = Http::acceptJson()->timeout(180);

        if ($token) {
            $request = $request->withToken($token);
        }

        $res = $request->post(Pengaturan::get('terjemahan_perkata_api_url'), [
            'model' => Pengaturan::get('terjemahan_perkata_model'),
            'max_tokens' => (int) Pengaturan::get('terjemahan_perkata_max_tokens', 8000),
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM],
                ['role' => 'user', 'content' => "Teks doa:\n{$teksArab}"],
            ],
        ])->throw();

        $teks = $res->json('choices.0.message.content');
        $teks = trim(preg_replace('/^```(?:json)?|```$/m', '', $teks));

        $data = json_decode($teks, true);
        if (! is_array($data) || $data === []) {
            throw new \RuntimeException('Respons AI bukan JSON array yang valid.');
        }

        foreach ($data as $k) {
            if (! is_array($k) || empty($k['kata_arab']) || empty($k['arti_kata'])) {
                throw new \RuntimeException('Ada item tanpa kata_arab/arti_kata.');
            }
        }

        return array_values($data);
    }
}
