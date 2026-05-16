@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('page-title', 'Edit Pengguna')

@section('content')
    <div class="mx-auto max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h1 class="text-xl font-bold text-gray-900">Edit Pengguna</h1>
        <p class="mt-1 text-sm text-gray-500">Perbarui informasi akun pengguna.</p>

        <x-messages.validation-errors />

        <form action="{{ route('users.update', $user) }}" method="POST" class="mt-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username', $user->username) }}"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            <div>
                <label for="role" class="mb-1 block text-sm font-medium text-gray-700">Role</label>
                <select
                    id="role"
                    name="role"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
                    @foreach (['super', 'admin', 'analyst', 'supervisor', 'manager'] as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-lg border border-amber-200 p-4">
                <p class="text-sm font-semibold text-amber-800">Reset Password Pengguna</p>
                <p class="mt-1 text-xs text-amber-700">
                    Kosongkan jika tidak ingin reset password. Jika diisi, pengguna wajib ganti password lagi saat login berikutnya.
                </p>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password Baru</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                        >
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                        >
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <a
                    href="{{ route('users.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-800"
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
