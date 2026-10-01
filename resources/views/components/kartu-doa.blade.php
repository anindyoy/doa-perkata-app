@props(['doa'])
<a href="{{ route('doa.show', $doa) }}"
   class="group flex h-full flex-col justify-between rounded-xl border border-tinta/10 bg-white p-5 transition-colors hover:border-safron focus:outline-none focus:ring-2 focus:ring-safron dark:border-white/10 dark:bg-malam-2 dark:hover:border-safron">
    <div>
        @if ($doa->kategori)
            <span class="mb-2 inline-block rounded bg-tinta/5 px-2 py-0.5 text-xs font-medium text-tinta-muda dark:bg-white/10 dark:text-slate-300">{{ $doa->kategori->nama }}</span>
        @endif
        <h3 class="font-serif text-xl font-semibold leading-snug">{{ $doa->judul }}</h3>
    </div>
    <p class="teks-arab mt-4 line-clamp-2 text-2xl leading-loose text-tinta dark:text-slate-100" dir="rtl">{{ $doa->teks_arab }}</p>
</a>
