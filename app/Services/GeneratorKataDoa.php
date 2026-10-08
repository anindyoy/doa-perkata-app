<?php

namespace App\Services;

use App\Models\Doa;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GeneratorKataDoa
{
    public const SYSTEM = <<<'TXT'
Kamu adalah ahli bahasa Arab yang membantu aplikasi doa harian.
Pecah teks doa Arab menjadi kata per kata, sesuai urutan teks. Untuk tiap kata beri:
- kata_arab: kata persis seperti di teks (pertahankan harakat), tanpa mengubah/menambah kata
- transliterasi_kata: cara baca latin kata tersebut
- arti_kata: arti singkat dalam Bahasa Indonesia sesuai konteks doa (boleh menggabungkan partikel seperti و/ب/ل ke kata berikutnya jika memang melekat)
Abaikan tanda waqf/ayat yang berdiri sendiri. Jangan beri penjelasan lain.
Balas HANYA JSON array tanpa markdown, contoh:
[{"kata_arab":"الْحَمْدُ","transliterasi_kata":"al-ḥamdu","arti_kata":"segala puji"}]
TXT;

    public function siap(): bool
    {
        return filled(Pengaturan::get('terjemahan_perkata_api_url'))
            && filled(Pengaturan::get('terjemahan_perkata_model'));
    }

    public function pesanBelumSiap(): string
    {
        return 'URL API dan model terjemahan per kata harus diatur di menu Pengaturan.';
    }

    /**
     * Generate arti per kata untuk satu doa dan simpan sebagai draf AI.
     * Selalu tulis ulang (hapus semua kata lama termasuk yang terverifikasi).
     *
     * @throws \RuntimeException bila konfigurasi belum lengkap atau respons AI tidak valid.
     */
    public function generate(Doa $doa): int
    {
        if (! $this->siap()) {
            throw new \RuntimeException($this->pesanBelumSiap());
        }

        $teksArab = (string) $doa->getAttribute('teks_arab');
        if ($teksArab === '') {
            throw new \RuntimeException('Teks Arab doa masih kosong.');
        }

        $kata = $this->mintaAi($teksArab);

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

        return count($kata);
    }

    /**
     * @return list<array{kata_arab: string, transliterasi_kata?: ?string, arti_kata: string}>
     *
     * @throws \RuntimeException
     */
    public function mintaAi(string $teksArab): array
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
        $teks = trim(preg_replace('/^```(?:json)?|```$/m', '', (string) $teks));

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
