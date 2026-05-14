@extends('layouts.app')

@section('title', 'Tambah Ruangan')

@section('content')
    <div class="mx-auto max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h1 class="text-xl font-bold text-gray-900">Tambah Ruangan</h1>
        <p class="mt-1 text-sm text-gray-500">Buat ruangan baru untuk sistem.</p>

        <form action="{{ route('rooms.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nama Ruangan <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            <div>
                <label for="room_number" class="mb-1 block text-sm font-medium text-gray-700">Nomor Ruangan <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="room_number"
                    name="room_number"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            <div>
                <label for="class" class="mb-1 block text-sm font-medium text-gray-700">Kelas <span class="text-red-500">*</span></label>
                <select
                    id="class"
                    name="class"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
                    <option value="" >--Pilih kelas--</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <a
                    href="{{ route('rooms.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-800 cursor-pointer"
                >
                    Tambah Ruangan
                </button>
            </div>
        </form>
    </div>
@endsection
