@extends('layouts.app')
@section('judul', 'Doa Tersimpan')

@section('isi')
<h1 class="font-serif text-4xl font-semibold">Doa Tersimpan</h1>

@if ($doa->isEmpty())
    <p class="mt-8 rounded-xl border border-dashed border-tinta/20 p-10 text-center text-tinta/60 dark:border-white/15 dark:text-slate-400">
        Belum ada doa yang disimpan. <a class="underline" href="{{ route('beranda') }}">Jelajahi doa</a> lalu tekan “Simpan Doa”.
    </p>
@else
    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        @foreach ($doa as $d)
            <div class="flex flex-col gap-2">
                <div class="flex-1"><x-kartu-doa :doa="$d" /></div>
                <form method="POST" action="{{ route('doa.hapus', $d) }}">@csrf @method('DELETE')
                    <button class="text-sm text-tinta/60 underline hover:text-safron dark:text-slate-400">Hapus dari daftar</button>
                </form>
            </div>
        @endforeach
    </div>
    <div class="mt-8">{{ $doa->links() }}</div>
@endif
@endsection
