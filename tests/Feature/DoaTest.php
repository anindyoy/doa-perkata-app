<?php

use App\Models\Doa;
use App\Models\KataDoa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buatDoaUntukCakupan(string $slug): Doa
{
    return Doa::create([
        'judul' => "Doa {$slug}",
        'slug' => $slug,
        'teks_arab' => 'نَصٌّ',
        'terjemahan' => 'Teks',
    ]);
}

it('hanya memilih doa yang memiliki kata dan seluruh katanya terverifikasi', function () {
    $doaKosong = buatDoaUntukCakupan('doa-kosong');
    $doaTerverifikasi = buatDoaUntukCakupan('doa-terverifikasi');
    $doaBelumTerverifikasi = buatDoaUntukCakupan('doa-belum-terverifikasi');
    $doaCampuran = buatDoaUntukCakupan('doa-campuran');

    KataDoa::create([
        'doa_id' => $doaTerverifikasi->id,
        'kata_arab' => 'نَصٌّ',
        'arti_kata' => 'teks',
        'urutan' => 1,
        'status' => 'terverifikasi',
    ]);
    KataDoa::create([
        'doa_id' => $doaBelumTerverifikasi->id,
        'kata_arab' => 'نَصٌّ',
        'arti_kata' => 'teks',
        'urutan' => 1,
        'status' => 'diterjemahkan_ai',
    ]);
    KataDoa::create([
        'doa_id' => $doaCampuran->id,
        'kata_arab' => 'نَصٌّ',
        'arti_kata' => 'teks',
        'urutan' => 1,
        'status' => 'terverifikasi',
    ]);
    KataDoa::create([
        'doa_id' => $doaCampuran->id,
        'kata_arab' => 'آخَرُ',
        'arti_kata' => 'lain',
        'urutan' => 2,
        'status' => 'diterjemahkan_ai',
    ]);

    expect(Doa::terverifikasi()->pluck('id')->all())->toBe([$doaTerverifikasi->id]);
});

it('mengurutkan relasi kata doa berdasarkan urutan', function () {
    $doa = buatDoaUntukCakupan('doa-berurutan');

    foreach ([2, 1] as $urutan) {
        KataDoa::create([
            'doa_id' => $doa->id,
            'kata_arab' => "kata-{$urutan}",
            'arti_kata' => "arti-{$urutan}",
            'urutan' => $urutan,
            'status' => 'terverifikasi',
        ]);
    }

    expect($doa->kataDoa->pluck('urutan')->all())->toBe([1, 2]);
});
