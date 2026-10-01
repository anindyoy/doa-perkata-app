<?php

namespace App\Http\Controllers;

use App\Models\Doa;
use App\Models\Kategori;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));
        $slugKategori = trim((string) $request->query('kategori', ''));

        $doa = Doa::with('kategori')
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($q) use ($cari) {
                    $q->where('judul', 'like', "%{$cari}%")
                        ->orWhere('teks_arab', 'like', "%{$cari}%")
                        ->orWhere('transliterasi', 'like', "%{$cari}%");
                });
            })
            ->when($slugKategori !== '', fn ($q) => $q->whereHas(
                'kategori', fn ($k) => $k->where('slug', $slugKategori)
            ))
            ->orderBy('urutan')->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('beranda', [
            'doa' => $doa,
            'kategori' => Kategori::orderBy('urutan')->get(),
            'cari' => $cari,
            'slugKategori' => $slugKategori,
        ]);
    }
}
