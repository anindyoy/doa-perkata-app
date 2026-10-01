@extends('layouts.app')
@section('judul', 'Masuk')

@section('isi')
<div class="mx-auto max-w-md">
    <h1 class="font-serif text-4xl font-semibold">Masuk</h1>
    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="block w-full rounded-lg border border-tinta/20 bg-white px-3 py-2.5 text-sm focus:border-safron focus:ring-safron dark:border-white/15 dark:bg-malam-2">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="kata_sandi" class="mb-1 block text-sm font-medium">Kata sandi</label>
            <input id="kata_sandi" type="password" name="kata_sandi" required autocomplete="current-password" class="block w-full rounded-lg border border-tinta/20 bg-white px-3 py-2.5 text-sm focus:border-safron focus:ring-safron dark:border-white/15 dark:bg-malam-2">
            @error('kata_sandi') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ingat" value="1" class="rounded border-tinta/30 text-tinta focus:ring-safron"> Ingat saya</label>
        <button class="w-full rounded-lg bg-tinta px-5 py-2.5 text-sm font-medium text-white hover:bg-tinta-muda focus:outline-none focus:ring-2 focus:ring-safron dark:bg-safron">Masuk</button>
    </form>
    <p class="mt-4 text-sm">Belum punya akun? <a class="underline" href="{{ route('register') }}">Daftar</a></p>
</div>
@endsection
