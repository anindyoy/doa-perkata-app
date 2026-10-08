@extends('layouts.app')
@section('judul', $doa->judul)

@section('isi')
<article x-data="{ tampilArti: true, tampilLatin: true }">
    <a href="{{ route('beranda') }}" class="text-sm text-tinta-muda underline dark:text-slate-400">&larr; Semua doa</a>

    <header class="mt-4 flex flex-col items-start gap-4">
        <div class="w-full">
            @if ($doa->kategori)
                <a href="{{ route('beranda', ['kategori' => $doa->kategori->slug]) }}" class="text-sm font-medium text-safron">{{ $doa->kategori->nama }}</a>
            @endif
            <h1 class="font-serif text-3xl font-semibold md:text-4xl">{{ $doa->judul }}</h1>

            @auth
                @if ($tersimpan)
                    <form method="POST" action="{{ route('doa.hapus', $doa) }}">@csrf @method('DELETE')
                        <button class="inline-flex items-center gap-2 rounded-lg bg-safron px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-safron/50">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1Z"/></svg> Tersimpan
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('doa.simpan', $doa) }}">@csrf
                        <button class="inline-flex items-center gap-2 rounded-lg border border-tinta/25 px-4 py-2 text-sm font-medium hover:border-safron focus:outline-none focus:ring-2 focus:ring-safron dark:border-white/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1Z"/></svg> Simpan Doa
                        </button>
                    </form>
                @endif
            @else
                <a href="{{ route('login', ['kembali' => '/'.request()->path(), 'pesan' => 'simpan']) }}" class="inline-flex items-center gap-2 rounded-lg border border-tinta/25 px-4 py-2 text-sm font-medium hover:border-safron dark:border-white/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1Z"/></svg> Simpan
                </a>
            @endauth

            <div class="flex flex-wrap items-center gap-3 mt-2">
                @if ($doa->transliterasi)
                    <label class="inline-flex cursor-pointer items-center gap-3 text-sm font-medium">
                        <input type="checkbox" x-model="tampilLatin" class="peer sr-only">
                        <span class="relative h-6 w-11 rounded-full bg-tinta/25 transition-colors after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-tinta peer-checked:after:translate-x-full peer-focus-visible:ring-2 peer-focus-visible:ring-safron dark:bg-white/20 dark:peer-checked:bg-safron"></span>
                        Tampilkan Latin
                    </label>
                @endif
                @if ($doa->kataDoa->isNotEmpty())
                    <label class="inline-flex cursor-pointer items-center gap-3 text-sm font-medium">
                        <input type="checkbox" x-model="tampilArti" class="peer sr-only">
                        <span class="relative h-6 w-11 rounded-full bg-tinta/25 transition-colors after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-tinta peer-checked:after:translate-x-full peer-focus-visible:ring-2 peer-focus-visible:ring-safron dark:bg-white/20 dark:peer-checked:bg-safron"></span>
                        Tampilkan Arti per Kata
                    </label>
                @endif
            </div>
        </div>
    </header>

    <section class="mt-8 rounded-2xl border border-tinta/10 bg-white p-6 md:p-10 dark:border-white/10 dark:bg-malam-2" aria-label="Teks doa">
        <p class="teks-arab text-4xl leading-[2.1] md:text-5xl md:leading-[2.1]" dir="rtl" lang="ar">{{ $doa->teks_arab }}</p>
        @if ($doa->transliterasi)
            <p x-show="tampilLatin" x-transition class="mt-6 text-lg italic text-tinta/80 dark:text-slate-300">{{ $doa->transliterasi }}</p>
        @endif
        @if ($doa->catatan)
            <p class="mt-4 inline-block rounded bg-safron/15 px-3 py-1 text-sm text-safron">{{ $doa->catatan }}</p>
        @endif
    </section>

    @if ($doa->kataDoa->isNotEmpty())
        <section class="mt-10" aria-label="Arti per kata">
            <h2 class="font-serif text-2xl font-semibold">Arti per kata</h2>

            @if ($adaBelumTerverifikasi)
                <p class="mt-3 text-sm text-tinta/70 dark:text-slate-400" x-show="tampilArti">
                    Terjemah perkata oleh AI
                </p>
            @endif

            <ul x-show="tampilArti" class="mt-5 flex flex-wrap gap-3" dir="rtl">
                @foreach ($doa->kataDoa as $kata)
                    <li class="min-w-[7rem] flex-1 basis-[7rem] rounded-xl border border-tinta/10 bg-white p-3 text-center sm:flex-none dark:border-white/10 dark:bg-malam-2">
                        <div class="teks-arab text-center text-3xl leading-loose" lang="ar">{{ $kata->kata_arab }}</div>
                        @if ($kata->transliterasi_kata)
                            <div class="text-xs italic text-tinta/60 dark:text-slate-400" dir="ltr">{{ $kata->transliterasi_kata }}</div>
                        @endif
                        <div class="mt-1 text-sm font-medium" dir="ltr">{{ $kata->arti_kata }}</div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-10 max-w-3xl">
        <h2 class="font-serif text-2xl font-semibold">Terjemahan lengkap</h2>
        <p class="mt-2 text-lg leading-relaxed">{{ $doa->terjemahan }}</p>
        @if ($doa->referensi_sumber)
            <p class="mt-4 text-sm text-tinta/60 dark:text-slate-400">Sumber: {{ $doa->referensi_sumber }}</p>
        @endif
    </section>
</article>
@endsection
