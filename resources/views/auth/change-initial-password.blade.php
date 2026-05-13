@extends('layouts.auth')

@section('title', 'Ubah Password Awal')
@section('auth-heading', 'Ubah Password Awal Anda')

@section('auth-content')
    <p class="mb-6 text-sm text-gray-600">
        Demi keamanan akun, Anda wajib mengganti password default sebelum melanjutkan.
    </p>

    @if ($errors->any())
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('password.change.update') }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="old_password" class="mb-1 block text-sm font-medium text-gray-700">Password Lama</label>
            <input
                id="old_password"
                name="old_password"
                type="password"
                required
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
            >
        </div>

        <div>
            <label for="new_password" class="mb-1 block text-sm font-medium text-gray-700">Password Baru</label>
            <input
                id="new_password"
                name="new_password"
                type="password"
                required
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
            >
            <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter, mengandung angka dan simbol.</p>
        </div>

        <div>
            <label for="new_password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
            <input
                id="new_password_confirmation"
                name="new_password_confirmation"
                type="password"
                required
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700"
        >
            Simpan Password Baru
        </button>
    </form>
@endsection
