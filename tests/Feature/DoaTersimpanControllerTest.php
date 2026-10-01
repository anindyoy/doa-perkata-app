<?php

namespace Tests\Feature;

use App\Models\Doa;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoaTersimpanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_guests_to_login_from_the_saved_list(): void
    {
        $this->get(route('tersimpan'))->assertRedirect(route('login'));
    }

    public function test_it_lists_saved_prayers_for_the_authenticated_user(): void
    {
        $pengguna = $this->buatPengguna();
        $doa = $this->buatDoa();
        $pengguna->doaTersimpan()->attach($doa->id);

        $response = $this->actingAs($pengguna)->get(route('tersimpan'));

        $response->assertOk()->assertViewIs('doa.tersimpan');
        $this->assertSame([$doa->id], $response->viewData('doa')->getCollection()->pluck('id')->all());
    }

    public function test_it_saves_a_prayer_without_creating_duplicate_pivot_rows(): void
    {
        $pengguna = $this->buatPengguna();
        $doa = $this->buatDoa();

        $this->actingAs($pengguna)
            ->from(route('doa.show', $doa))
            ->post(route('doa.simpan', $doa))
            ->assertRedirect(route('doa.show', $doa))
            ->assertSessionHas('info', 'Doa disimpan.');
        $this->post(route('doa.simpan', $doa));

        $this->assertSame(1, $pengguna->doaTersimpan()->whereKey($doa->id)->count());
        $this->assertTrue($doa->disimpanOleh()->whereKey($pengguna->id)->exists());
    }

    public function test_it_removes_a_prayer_from_the_saved_list(): void
    {
        $pengguna = $this->buatPengguna();
        $doa = $this->buatDoa();
        $pengguna->doaTersimpan()->attach($doa->id);

        $this->actingAs($pengguna)
            ->delete(route('doa.hapus', $doa))
            ->assertSessionHas('info', 'Doa dihapus dari daftar tersimpan.');

        $this->assertSame(0, $pengguna->doaTersimpan()->whereKey($doa->id)->count());
    }

    private function buatPengguna(): Pengguna
    {
        return Pengguna::create([
            'nama' => 'Pengguna Uji',
            'email' => 'uji@example.test',
            'kata_sandi' => 'kata-sandi-rahasia',
        ]);
    }

    private function buatDoa(): Doa
    {
        return Doa::create([
            'judul' => 'Doa Uji',
            'slug' => 'doa-uji',
            'teks_arab' => 'نَصٌّ',
            'terjemahan' => 'Teks',
        ]);
    }
}
