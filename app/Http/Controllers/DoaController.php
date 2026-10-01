<?php

namespace App\Http\Controllers;

use App\Models\Doa;
use App\Models\Pengaturan;
use Illuminate\Http\Request;

class DoaController extends Controller
{
    public function show(Request $request, Doa $doa)
    {
        $doa->load(['kategori', 'kataDoa']);

        $tersimpan = $request->user()
            ? $request->user()->doaTersimpan()->whereKey($doa->id)->exists()
            : false;

        return view('doa.show', [
            'doa' => $doa,
            'tersimpan' => $tersimpan,
            'adaBelumTerverifikasi' => $doa->kataDoa->contains('status', '!=', 'terverifikasi'),
        ]);
    }

    /** GET /doa/acak — redirect ke doa acak (tanpa halaman perantara). */
    public function acak(Request $request)
    {
        $query = Doa::query();

        if (Pengaturan::aktif('doa_acak_hanya_terverifikasi', true)) {
            $query->terverifikasi();
        }

        $riwayat = $request->session()->get('doa_acak_riwayat', []);

        // Kalau setelah exclude riwayat tidak ada sisa (doa eligible <= 5), riwayat diabaikan.
        if ((clone $query)->whereNotIn('id', $riwayat)->exists()) {
            $query->whereNotIn('id', $riwayat);
        }

        $doa = $query->inRandomOrder()->first();

        if (! $doa) {
            return redirect()->route('beranda')
                ->with('info', 'Belum ada doa yang bisa ditampilkan secara acak.');
        }

        $riwayat[] = $doa->id;
        $request->session()->put('doa_acak_riwayat', array_slice($riwayat, -5));

        return redirect()->route('doa.show', $doa);
    }
}
