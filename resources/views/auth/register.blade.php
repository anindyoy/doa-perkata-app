@extends('layouts.app')
@section('judul', 'Daftar')

@section('isi')
<div class="mx-auto max-w-md">
    <h1 class="font-serif text-4xl font-semibold">Daftar</h1>
    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="nama" class="mb-1 block text-sm font-medium">Nama</label>
            <input id="nama" name="nama" value="{{ old('nama') }}" required autocomplete="name" class="block w-full rounded-lg border border-tinta/20 bg-white px-3 py-2.5 text-sm focus:border-safron focus:ring-safron dark:border-white/15 dark:bg-malam-2">
            @error('nama') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="block w-full rounded-lg border border-tinta/20 bg-white px-3 py-2.5 text-sm focus:border-safron focus:ring-safron dark:border-white/15 dark:bg-malam-2">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="kata_sandi" class="mb-1 block text-sm font-medium">Kata sandi</label>
            <input id="kata_sandi" type="password" name="kata_sandi" required autocomplete="new-password" class="block w-full rounded-lg border border-tinta/20 bg-white px-3 py-2.5 text-sm focus:border-safron focus:ring-safron dark:border-white/15 dark:bg-malam-2">
            @error('kata_sandi') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="kata_sandi_confirmation" class="mb-1 block text-sm font-medium">Ulangi kata sandi</label>
            <input id="kata_sandi_confirmation" type="password" name="kata_sandi_confirmation" required autocomplete="new-password" class="block w-full rounded-lg border border-tinta/20 bg-white px-3 py-2.5 text-sm focus:border-safron focus:ring-safron dark:border-white/15 dark:bg-malam-2">
        </div>
        <button class="w-full rounded-lg bg-tinta px-5 py-2.5 text-sm font-medium text-white hover:bg-tinta-muda focus:outline-none focus:ring-2 focus:ring-safron dark:bg-safron">Buat akun</button>
    </form>
    <p class="mt-4 text-sm">Sudah punya akun? <a class="underline" href="{{ route('login') }}">Masuk</a></p>
</div>
@endsection
