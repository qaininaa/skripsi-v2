@extends('layouts.app')

@section('title', 'Manajemen Lokasi')
@section('page-title', 'Manajemen Lokasi')

@section('content')
    @php
        $frequencyLabels = [
            'operational' => 'Operasional',
            'daily'       => 'Harian',
            'weekly'      => 'Mingguan',
            'monthly'     => 'Bulanan',
            'semi_annual' => '6 Bulanan',
        ];
    @endphp

    <x-messages.success-message />
    <x-messages.error-message />

    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Daftar Lokasi</h2>
            <p class="mt-1 text-sm text-gray-500">Kelola data lokasi pengambilan sampel.</p>
        </div>
        <a
            href="{{ route('location.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-800"
        >
            <span>+</span>
            <span>Tambah Lokasi</span>
        </a>
    </div>

    <form method="GET" action="{{ route('location.index') }}" class="mb-4 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
        <div class="grid gap-3 lg:grid-cols-[1fr_220px_auto_auto]">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama ruangan atau nomor lokasi..."
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >

            <select
                name="room_id"
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
                <option value="">Semua Ruangan</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected(request('room_id') === $room->id)>
                        {{ $room->name }}
                    </option>
                @endforeach
            </select>

            <button
                type="submit"
                class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-800 cursor-pointer"
            >
                Cari
            </button>

            <a
                href="{{ route('location.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-100"
            >
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Ruangan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">No. Lokasi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Tipe Pengukuran</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Frekuensi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Alert Total (T)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Alert Fungi (F)</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($locations as $index => $location)
                        <tr class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500">
                                {{ $locations->firstItem() + $loop->index }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-semibold text-gray-800">{{ $location->room->name }}</div>
                                <div class="text-xs text-gray-500">{{ $location->room->room_number }} &middot; Kelas {{ $location->room->class }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $location->loc_number }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm"><x-badges.measurement-type :type="$location->measurement_type" /></td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $frequencyLabels[$location->frequency] ?? $location->frequency }}
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-600">
                                <div>Batas Alert: {{ $location->alert_limit_total ?? '-' }}</div>
                                <div>Batas Aksi: {{ $location->alert_action_total ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-600">
                                <div>Batas Alert: {{ $location->alert_limit_fungi ?? '-' }}</div>
                                <div>Batas Aksi: {{ $location->alert_action_fungi ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <x-buttons.edit :href="route('location.edit', $location)" />
                                    <x-buttons.delete
                                        :action="route('location.destroy', $location)"
                                        :item-name="$location->loc_number"
                                        title="Hapus Lokasi"
                                        warning="Lokasi yang dihapus tidak dapat dikembalikan."
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                Data lokasi tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-4 py-3">
            {{ $locations->links() }}
        </div>
    </div>
@endsection
