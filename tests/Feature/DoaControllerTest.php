<?php

namespace Tests\Feature;

use App\Models\Doa;
use App\Models\KataDoa;
use App\Models\Pengaturan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_doa_detail_and_marks_unverified_words(): void
    {
        $doa = $this->buatDoa('doa-detail', 'diterjemahkan_ai');

        $response = $this->get(route('doa.show', $doa));

        $response->assertOk()->assertViewIs('doa.show');
        $this->assertFalse($response->viewData('tersimpan'));
        $this->assertTrue($response->viewData('adaBelumTerverifikasi'));
    }

    public function test_it_marks_a_doa_as_saved_for_the_authenticated_user(): void
    {
        $doa = $this->buatDoa('doa-tersimpan', 'terverifikasi');
        $pengguna = Pengguna::create([
            'nama' => 'Pengguna Uji',
            'email' => 'uji@example.test',
            'kata_sandi' => 'rahasia-panjang',
        ]);
        $pengguna->doaTersimpan()->attach($doa->id);

        $response = $this->actingAs($pengguna)->get(route('doa.show', $doa));

        $response->assertOk();
        $this->assertTrue($response->viewData('tersimpan'));
        $this->assertFalse($response->viewData('adaBelumTerverifikasi'));
    }

    public function test_it_redirects_to_an_available_verified_doa_and_tracks_recent_history(): void
    {
        $doaList = [];
        for ($index = 1; $index <= 6; $index++) {
            $doaList[] = $this->buatDoa("doa-acak-{$index}", 'terverifikasi');
        }
        $history = array_map(fn (Doa $doa) => $doa->id, array_slice($doaList, 0, 5));

        $response = $this->withSession(['doa_acak_riwayat' => $history])->get(route('doa.acak'));

        $response->assertRedirect(route('doa.show', $doaList[5]));
        $this->assertSame(array_merge(array_slice($history, 1), [$doaList[5]->id]), session('doa_acak_riwayat'));
    }

    public function test_it_ignores_history_when_it_contains_every_verified_doa(): void
    {
        $doa = $this->buatDoa('doa-satu-satunya', 'terverifikasi');

        $response = $this->withSession(['doa_acak_riwayat' => [$doa->id]])->get(route('doa.acak'));

        $response->assertRedirect(route('doa.show', $doa));
        $this->assertSame([$doa->id, $doa->id], session('doa_acak_riwayat'));
    }

    public function test_it_can_select_unverified_doa_when_the_setting_allows_it(): void
    {
        Pengaturan::where('kunci', 'doa_acak_hanya_terverifikasi')->update(['nilai' => '0']);
        $doa = $this->buatDoa('doa-belum-diverifikasi', null);

        $this->get(route('doa.acak'))->assertRedirect(route('doa.show', $doa));
    }

    public function test_it_redirects_home_with_an_information_message_when_no_doa_is_eligible(): void
    {
        $this->get(route('doa.acak'))
            ->assertRedirect(route('beranda'))
            ->assertSessionHas('info', 'Belum ada doa yang bisa ditampilkan secara acak.');
    }

    private function buatDoa(string $slug, ?string $status): Doa
    {
        $doa = Doa::create([
            'judul' => "Judul {$slug}",
            'slug' => $slug,
            'teks_arab' => 'نَصٌّ',
            'terjemahan' => 'Teks',
        ]);

        if ($status !== null) {
            KataDoa::create([
                'doa_id' => $doa->id,
                'kata_arab' => 'نَصٌّ',
                'arti_kata' => 'teks',
                'urutan' => 1,
                'status' => $status,
            ]);
        }

        return $doa;
    }
}
