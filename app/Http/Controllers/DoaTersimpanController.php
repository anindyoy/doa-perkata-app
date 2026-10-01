<?php

namespace App\Http\Controllers;

use App\Models\Doa;
use Illuminate\Http\Request;

class DoaTersimpanController extends Controller
{
    public function index(Request $request)
    {
        $doa = $request->user()->doaTersimpan()->with('kategori')->paginate(12);

        return view('doa.tersimpan', ['doa' => $doa]);
    }

    public function simpan(Request $request, Doa $doa)
    {
        // syncWithoutDetaching + unique constraint: tidak pernah dobel
        $request->user()->doaTersimpan()->syncWithoutDetaching([$doa->id]);

        return back()->with('info', 'Doa disimpan.');
    }

    public function hapus(Request $request, Doa $doa)
    {
        $request->user()->doaTersimpan()->detach($doa->id);

        return back()->with('info', 'Doa dihapus dari daftar tersimpan.');
    }
}
