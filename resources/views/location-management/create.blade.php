@extends('layouts.app')

@section('title', 'Tambah Lokasi')
@section('page-title', 'Tambah Lokasi')

@section('content')
    <div class="mx-auto max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h1 class="text-xl font-bold text-gray-900">Tambah Lokasi Baru</h1>
        <p class="mt-1 text-sm text-gray-500">Tambahkan lokasi pengambilan sampel baru.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('location.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            {{-- Ruangan --}}
            <div>
                <label for="room_id" class="mb-1 block text-sm font-medium text-gray-700">
                    Ruangan <span class="text-red-500">*</span>
                </label>
                <select
                    id="room_id"
                    name="room_id"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
                    <option value="">Pilih ruangan</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(old('room_id') === $room->id)>
                            {{ $room->name }} &mdash; {{ $room->room_number }} (Kelas {{ $room->class }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Nomor Lokasi --}}
            <div>
                <label for="loc_number" class="mb-1 block text-sm font-medium text-gray-700">
                    Nomor Lokasi <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="loc_number"
                    name="loc_number"
                    value="{{ old('loc_number') }}"
                    placeholder="Contoh: L-01"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            {{-- Tipe Pengukuran --}}
            <div>
                <label for="measurement_type" class="mb-1 block text-sm font-medium text-gray-700">
                    Tipe Pengukuran <span class="text-red-500">*</span>
                </label>
                <select
                    id="measurement_type"
                    name="measurement_type"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
                    <option value="">— Pilih Tipe Pengukuran —</option>
                    <option value="Settle Plate" @selected(old('measurement_type') === 'Settle Plate')>Settle Plate</option>
                    <option value="Contact Plate" @selected(old('measurement_type') === 'Contact Plate')>Contact Plate</option>
                    <option value="Air Sampler" @selected(old('measurement_type') === 'Air Sampler')>Air Sampler</option>
                    <option value="Swab" @selected(old('measurement_type') === 'Swab')>Swab</option>
                </select>
            </div>

            {{-- Frekuensi Pemantauan --}}
            <div>
                <label for="frequency" class="mb-1 block text-sm font-medium text-gray-700">
                    Frekuensi Pemantauan <span class="text-red-500">*</span>
                </label>
                <select
                    id="frequency"
                    name="frequency"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
                    <option value="">— Pilih Frekuensi Pemantauan —</option>
                    <option value="operational" @selected(old('frequency') === 'operational')>Operasional</option>
                    <option value="daily" @selected(old('frequency') === 'daily')>Harian</option>
                    <option value="weekly" @selected(old('frequency') === 'weekly')>Mingguan</option>
                    <option value="monthly" @selected(old('frequency') === 'monthly')>Bulanan</option>
                    <option value="semi_annual" @selected(old('frequency') === 'semi_annual')>Semi-Tahunan</option>
                </select>
            </div>

            {{-- Batas Alert Total --}}
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Batas Alert Total/B+F (CFU)</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="alert_limit_total" class="mb-1 block text-xs font-medium text-gray-600">Alert Limit</label>
                        <input
                            type="number"
                            id="alert_limit_total"
                            name="alert_limit_total"
                            value="{{ old('alert_limit_total', 0) }}"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                        >
                    </div>
                    <div>
                        <label for="alert_action_total" class="mb-1 block text-xs font-medium text-gray-600">Action Limit</label>
                        <input
                            type="number"
                            id="alert_action_total"
                            name="alert_action_total"
                            value="{{ old('alert_action_total', 0) }}"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                        >
                    </div>
                </div>
            </div>

            {{-- Batas Alert Fungi --}}
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Batas Alert Jamur/Fungi (CFU)</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="alert_limit_fungi" class="mb-1 block text-xs font-medium text-gray-600">Alert Limit</label>
                        <input
                            type="number"
                            id="alert_limit_fungi"
                            name="alert_limit_fungi"
                            value="{{ old('alert_limit_fungi', 0) }}"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                        >
                    </div>
                    <div>
                        <label for="alert_action_fungi" class="mb-1 block text-xs font-medium text-gray-600">Action Limit</label>
                        <input
                            type="number"
                            id="alert_action_fungi"
                            name="alert_action_fungi"
                            value="{{ old('alert_action_fungi', 0) }}"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                        >
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <a
                    href="{{ route('location.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-800 cursor-pointer"
                >
                    Simpan Lokasi
                </button>
            </div>
        </form>
    </div>
@endsection
