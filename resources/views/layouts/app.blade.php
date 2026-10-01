<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Doa Perkata') — Doa Perkata</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('tema');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Instrument+Sans:wght@400;500;600&family=Newsreader:opsz,wght@6..72,500;6..72,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans">
<nav class="border-b border-tinta/10 bg-white/70 backdrop-blur dark:border-white/10 dark:bg-malam-2/80">
    <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <a href="{{ route('beranda') }}" class="font-serif text-2xl font-semibold tracking-tight text-tinta dark:text-white">
            Doa <span class="font-arab text-safron">perkata</span>
        </a>

        <button data-collapse-toggle="menu-utama" type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-tinta hover:bg-tinta/5 focus:outline-none focus:ring-2 focus:ring-safron md:hidden dark:text-slate-200 dark:hover:bg-white/10"
                aria-controls="menu-utama" aria-expanded="false">
            <span class="sr-only">Buka menu</span>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 17 14" aria-hidden="true"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/></svg>
        </button>

        <div id="menu-utama" class="hidden w-full md:block md:w-auto">
            <ul class="flex flex-col items-start gap-1 text-sm font-medium md:flex-row md:items-center md:gap-2">
                <li><a href="{{ route('beranda') }}" class="block rounded-lg px-3 py-2 hover:bg-tinta/5 dark:hover:bg-white/10">Semua Doa</a></li>
                <li><a href="{{ route('doa.acak') }}" class="block rounded-lg bg-safron px-3 py-2 text-white hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-safron/50">Doa Acak</a></li>
                @auth
                    <li><a href="{{ route('tersimpan') }}" class="block rounded-lg px-3 py-2 hover:bg-tinta/5 dark:hover:bg-white/10">Doa Tersimpan</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="block rounded-lg px-3 py-2 hover:bg-tinta/5 dark:hover:bg-white/10">Keluar ({{ \Illuminate\Support\Str::words(auth()->user()->nama, 1, '') }})</button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 hover:bg-tinta/5 dark:hover:bg-white/10">Masuk</a></li>
                    <li><a href="{{ route('register') }}" class="block rounded-lg border border-tinta/20 px-3 py-2 hover:bg-tinta/5 dark:border-white/20 dark:hover:bg-white/10">Daftar</a></li>
                @endauth
                <li>
                    <button type="button" x-data aria-label="Ganti tema terang/gelap"
                            @click="const d = document.documentElement.classList.toggle('dark'); try { localStorage.setItem('tema', d ? 'dark' : 'light') } catch (e) {}"
                            class="rounded-lg p-2 hover:bg-tinta/5 focus:outline-none focus:ring-2 focus:ring-safron dark:hover:bg-white/10">
                        <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                        <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="mx-auto max-w-5xl px-4 py-8 md:py-12">
    @if (session('info'))
        <div class="mb-6 rounded-lg border border-tinta/15 bg-white px-4 py-3 text-sm dark:border-white/10 dark:bg-malam-2" role="status">{{ session('info') }}</div>
    @endif
    @yield('isi')
</main>

<footer class="mx-auto max-w-5xl px-4 pb-10 text-xs text-tinta/60 dark:text-slate-400">
    Sumber teks doa: <a class="underline" href="https://github.com/fitrahive/dua-dhikr">fitrahive/dua-dhikr</a> (MIT).
    Arti per kata berlabel “draf AI” belum dicek manusia.
</footer>
</body>
</html>
