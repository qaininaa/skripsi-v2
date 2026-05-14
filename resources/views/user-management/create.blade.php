@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="mx-auto max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h1 class="text-xl font-bold text-gray-900">Tambah Pengguna</h1>
        <p class="mt-1 text-sm text-gray-500">Buat akun pengguna baru untuk sistem.</p>

        <form action="{{ route('users.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nama <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            <div>
                <label for="role" class="mb-1 block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                <select
                    id="role"
                    name="role"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
                    <option value="" >--Pilih role--</option>
                    <option value="super">Super</option>
                    <option value="admin">Admin</option>
                    <option value="analyst">Analyst</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="manager">Manager</option>
                </select>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
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
                    class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-800 cursor-pointer"
                >
                    Tambah User
                </button>
            </div>
        </form>
    </div>
@endsection
