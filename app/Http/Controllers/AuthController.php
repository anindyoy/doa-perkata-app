<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function formLogin(Request $request)
    {
        $this->simpanTujuan($request);

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'kata_sandi' => ['required'],
        ]);

        if (! Auth::attempt(
            ['email' => $data['email'], 'password' => $data['kata_sandi']],
            $request->boolean('ingat')
        )) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('beranda'));
    }

    public function formRegister(Request $request)
    {
        $this->simpanTujuan($request);

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:pengguna,email'],
            'kata_sandi' => ['required', 'confirmed', Password::min(8)],
        ]);

        $pengguna = Pengguna::create($data);

        Auth::login($pengguna);
        $request->session()->regenerate();

        return redirect()->intended(route('beranda'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda');
    }

    /** ?kembali=/doa/xyz -> setelah login kembali ke halaman itu (hanya path lokal). */
    private function simpanTujuan(Request $request): void
    {
        $kembali = (string) $request->query('kembali', '');

        if ($kembali !== '' && str_starts_with($kembali, '/') && ! str_starts_with($kembali, '//')) {
            $request->session()->put('url.intended', url($kembali));
        }
    }
}
