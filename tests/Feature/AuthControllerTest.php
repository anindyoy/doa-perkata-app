<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_remembers_a_local_login_destination(): void
    {
        $response = $this->get(route('login', ['kembali' => '/doa/tujuan']));

        $response->assertOk()->assertViewIs('auth.login');
        $this->assertSame(url('/doa/tujuan'), session('url.intended'));
    }

    public function test_it_does_not_remember_an_external_login_destination(): void
    {
        $this->get('/masuk?kembali=%2F%2Foutside.example')
            ->assertOk()
            ->assertSessionMissing('url.intended');
    }

    public function test_it_renders_registration_and_remembers_a_local_destination(): void
    {
        $response = $this->get(route('register', ['kembali' => '/doa/tujuan']));

        $response->assertOk()->assertViewIs('auth.register');
        $this->assertSame(url('/doa/tujuan'), session('url.intended'));
    }

    public function test_it_returns_an_error_when_login_credentials_are_incorrect(): void
    {
        $response = $this->from('/masuk')->post('/masuk', [
            'email' => 'unknown@example.test',
            'kata_sandi' => 'password-salah',
        ]);

        $response->assertRedirect('/masuk')->assertSessionHasErrors([
            'email' => 'Email atau kata sandi salah.',
        ]);
        $this->assertGuest();
    }

    public function test_it_authenticates_a_user_and_redirects_to_the_intended_page(): void
    {
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
        $this->assertTrue(Hash::check('kata-sandi-rahasia', $pengguna->kata_sandi));
    }

    public function test_it_registers_and_authenticates_a_new_user(): void
    {
        $response = $this->post('/daftar', [
            'nama' => 'Pengguna Baru',
            'email' => 'baru@example.test',
            'kata_sandi' => 'kata-sandi-rahasia',
            'kata_sandi_confirmation' => 'kata-sandi-rahasia',
        ]);

        $response->assertRedirect(route('beranda'));
        $pengguna = Pengguna::where('email', 'baru@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($pengguna);
        $this->assertTrue(Hash::check('kata-sandi-rahasia', $pengguna->kata_sandi));
    }

    public function test_it_logs_out_and_returns_to_the_home_page(): void
    {
        $pengguna = Pengguna::create([
            'nama' => 'Pengguna Uji',
            'email' => 'logout@example.test',
            'kata_sandi' => 'kata-sandi-rahasia',
        ]);

        $this->actingAs($pengguna)->post(route('logout'))->assertRedirect(route('beranda'));

        $this->assertGuest();
    }
}
