<?php

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('mengingat tujuan login lokal', function () {
    $response = $this->get(route('login', ['kembali' => '/doa/tujuan']));

    $response->assertOk()->assertViewIs('auth.login');
    expect(session('url.intended'))->toBe(url('/doa/tujuan'));
});

it('tidak mengingat tujuan login eksternal', function () {
    $this->get('/masuk?kembali=%2F%2Foutside.example')
        ->assertOk()
        ->assertSessionMissing('url.intended');
});

it('menampilkan registrasi dan mengingat tujuan lokal', function () {
    $response = $this->get(route('register', ['kembali' => '/doa/tujuan']));

    $response->assertOk()->assertViewIs('auth.register');
    expect(session('url.intended'))->toBe(url('/doa/tujuan'));
});

it('mengembalikan galat saat kredensial login salah', function () {
    $response = $this->from('/masuk')->post('/masuk', [
        'email' => 'unknown@example.test',
        'kata_sandi' => 'password-salah',
    ]);

    $response->assertRedirect('/masuk')->assertSessionHasErrors([
        'email' => 'Email atau kata sandi salah.',
    ]);
    $this->assertGuest();
});

it('mengautentikasi pengguna dan mengarahkan ke halaman tujuan', function () {
    $pengguna = Pengguna::create([
        'nama' => 'Pengguna Uji',
        'email' => 'login@example.test',
        'kata_sandi' => 'kata-sandi-rahasia',
    ]);
    $this->get('/masuk?kembali=%2Fdoa%2Ftujuan');

    $this->post('/masuk', [
        'email' => $pengguna->email,
        'kata_sandi' => 'kata-sandi-rahasia',
        'ingat' => true,
    ])->assertRedirect(url('/doa/tujuan'));

    $this->assertAuthenticatedAs($pengguna);
    $this->assertDatabaseHas('pengguna', [
        'id' => $pengguna->id,
        'token_ingat' => $pengguna->fresh()->token_ingat,
    ]);
    expect(Hash::check('kata-sandi-rahasia', $pengguna->kata_sandi))->toBeTrue();
});

it('mendaftarkan dan mengautentikasi pengguna baru', function () {
    $response = $this->post('/daftar', [
        'nama' => 'Pengguna Baru',
        'email' => 'baru@example.test',
        'kata_sandi' => 'kata-sandi-rahasia',
        'kata_sandi_confirmation' => 'kata-sandi-rahasia',
    ]);

    $response->assertRedirect(route('beranda'));
    $pengguna = Pengguna::where('email', 'baru@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($pengguna);
    expect(Hash::check('kata-sandi-rahasia', $pengguna->kata_sandi))->toBeTrue();
});

it('keluar dan kembali ke beranda', function () {
    $pengguna = Pengguna::create([
        'nama' => 'Pengguna Uji',
        'email' => 'logout@example.test',
        'kata_sandi' => 'kata-sandi-rahasia',
    ]);

    $this->actingAs($pengguna)->post(route('logout'))->assertRedirect(route('beranda'));

    $this->assertGuest();
});
