@extends('layouts.app')

@section('title', 'Manajemen Ruangan')
@section('page-title', 'Manajemen Ruangan')

@section('content')
    @php
        $classColors = [
            'A' => 'bg-rose-100 text-rose-700',
            'B' => 'bg-blue-100 text-blue-700',
            'C' => 'bg-green-100 text-green-700',
            'D' => 'bg-amber-100 text-amber-700',
            'E' => 'bg-slate-100 text-slate-700',
        ];
    @endphp

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Daftar Ruangan</h2>
            <p class="mt-1 text-sm text-gray-500">Kelola data ruangan aplikasi.</p>
        </div>
        <a
            href="{{ route('rooms.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-800"
        >
            <span>+</span>
            <span>Tambah Ruangan</span>
        </a>
    </div>

    <form method="GET" action="{{ route('rooms.index') }}" class="mb-4 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
        <div class="grid gap-3 lg:grid-cols-[1fr_180px_auto_auto]">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama atau nomor ruangan..."
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >

            <select
                name="class"
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
                <option value="">Semua Kelas</option>
                @foreach (['A', 'B', 'C', 'D', 'E'] as $class)
                    <option value="{{ $class }}" @selected(request('class') === $class)>{{ $class }}</option>
                @endforeach
            </select>

            <button
                type="submit"
                class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-800 cursor-pointer"
            >
                Filter
            </button>

            <a
                href="{{ route('rooms.index') }}"
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nomor Ruangan</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Kelas</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rooms as $room)
                        <tr class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-800">
                                {{ $room->name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $room->room_number }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $classColors[$room->class] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $room->class }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <x-buttons.edit :href="route('rooms.edit', $room)" />
                                    <x-buttons.delete
                                        :action="route('rooms.destroy', $room)"
                                        :item-name="$room->name"
                                        title="Hapus Ruangan"
                                        warning="Ruangan yang dihapus tidak dapat dikembalikan."
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                Data ruangan tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-4 py-3">
            {{ $rooms->links() }}
        </div>
    </div>
@endsection
