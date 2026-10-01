@extends('layouts.app')
@section('judul', 'Beranda')

@section('isi')
<header class="mb-8 max-w-2xl">
    <h1 class="font-serif text-4xl font-semibold leading-tight md:text-5xl">Pahami setiap kata dalam doamu.</h1>
    <p class="mt-3 text-lg text-tinta/70 dark:text-slate-400">Doa harian dengan arti per kata, bukan hanya terjemahan satu kalimat.</p>

    <form method="GET" action="{{ route('beranda') }}" class="mt-6 flex gap-2" role="search">
        @if ($slugKategori) <input type="hidden" name="kategori" value="{{ $slugKategori }}"> @endif
        <label for="cari" class="sr-only">Cari doa</label>
        <input id="cari" name="cari" value="{{ $cari }}" type="search" placeholder="Cari doa, mis. “bangun tidur”"
               class="block w-full rounded-lg border border-tinta/20 bg-white px-4 py-2.5 text-sm placeholder:text-tinta/40 focus:border-safron focus:ring-safron dark:border-white/15 dark:bg-malam-2 dark:placeholder:text-slate-500">
        <button class="rounded-lg bg-tinta px-5 text-sm font-medium text-white hover:bg-tinta-muda focus:outline-none focus:ring-2 focus:ring-safron dark:bg-safron dark:hover:brightness-110">Cari</button>
    </form>
</header>

@if ($kategori->isNotEmpty())
    <div class="mb-8 flex flex-wrap gap-2 text-sm" aria-label="Kategori doa">
        @php $tautan = fn ($slug) => route('beranda', array_filter(['cari' => $cari, 'kategori' => $slug])); @endphp
        <a href="{{ $tautan(null) }}" class="rounded-full border px-4 py-1.5 {{ $slugKategori === '' ? 'border-tinta bg-tinta text-white dark:border-safron dark:bg-safron' : 'border-tinta/20 hover:border-safron dark:border-white/15' }}">Semua</a>
        @foreach ($kategori as $k)
            <a href="{{ $tautan($k->slug) }}" class="rounded-full border px-4 py-1.5 {{ $slugKategori === $k->slug ? 'border-tinta bg-tinta text-white dark:border-safron dark:bg-safron' : 'border-tinta/20 hover:border-safron dark:border-white/15' }}">{{ $k->nama }}</a>
        @endforeach
    </div>
@endif

@if ($doa->isEmpty())
    <p class="rounded-xl border border-dashed border-tinta/20 p-10 text-center text-tinta/60 dark:border-white/15 dark:text-slate-400">
        Tidak ada doa yang cocok. Coba kata kunci lain atau <a class="underline" href="{{ route('beranda') }}">lihat semua doa</a>.
    </p>
@else
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($doa as $d)
            <x-kartu-doa :doa="$d" />
        @endforeach
    </div>
    <div class="mt-8">{{ $doa->links() }}</div>
@endif
@endsection
