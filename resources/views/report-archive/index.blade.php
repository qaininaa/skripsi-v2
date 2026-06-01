@extends('layouts.app')

@section('title', $activeFolder === null ? 'Arsip Laporan' : 'Detail Arsip Laporan')
@section('page-title', $activeFolder === null ? 'Arsip Laporan' : 'Detail Arsip Laporan')

@section('content')
    <x-messages.success-message />
    <x-messages.error-message />

    <div class="mb-5">
        @if ($activeFolder === null)
            <h2 class="text-2xl font-bold text-gray-900">Arsip Laporan</h2>
            <p class="mt-1 text-sm text-gray-500">
                Pilih folder arsip terlebih dahulu, lalu cari laporan yang dibutuhkan.
            </p>
        @else
            <h2 class="text-2xl font-bold text-gray-900">Detail Arsip Laporan</h2>
            <p class="mt-1 text-sm text-gray-500">
                Cari dan buka laporan pada folder arsip yang sedang aktif.
            </p>
        @endif
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        @if ($activeFolder === null)
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach ($folders as $folder)
                    <a
                        href="{{ route('arsip-laporan.index', ['folder' => $folder['slug']]) }}"
                        class="block rounded-xl border border-gray-200 p-5 transition-colors hover:border-green-300 hover:bg-green-50/40"
                    >
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100">
                                <img src="{{ asset('icons/sidebar/archive.svg') }}" alt="" class="h-5 w-5">
                            </div>
                            <span class="text-sm font-medium text-gray-500">{{ $folder['count'] }} arsip</span>
                        </div>
                        <h3 class="text-lg font-semibold tracking-tight text-gray-900">{{ $folder['code'] }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ $folder['subtitle'] }}</p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a
                        href="{{ route('arsip-laporan.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-100"
                    >
                        Kembali
                    </a>
                    <div class="mt-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Folder Aktif</p>
                        <h3 class="text-lg font-semibold tracking-tight text-gray-900">{{ $activeFolder['code'] }}</h3>
                    </div>
                </div>
            </div>

            <form
                method="GET"
                action="{{ route('arsip-laporan.index') }}"
                class="mb-5 border-b border-gray-100 pb-4"
            >
                <input type="hidden" name="folder" value="{{ $activeFolder['slug'] }}">
                <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto]">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama produk atau batch..."
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                    >
                    <button
                        type="submit"
                        class="rounded-lg bg-green-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-700"
                    >
                        Cari
                    </button>
                    <a
                        href="{{ route('arsip-laporan.index', ['folder' => $activeFolder['slug']]) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-5 py-2 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-100"
                    >
                        Reset
                    </a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-100">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-3 py-3">No</th>
                            <th class="px-3 py-3">Tanggal</th>
                            <th class="px-3 py-3">Jenis Laporan</th>
                            <th class="px-3 py-3">Nama Produk</th>
                            <th class="px-3 py-3">Batch Produk</th>
                            <th class="px-3 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($reports as $report)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                    {{ ($reports->firstItem() ?? 0) + $loop->index }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                    {{ $report->created_at->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-700">
                                    Annex {{ $report->reportTemplate?->annex_number ?? '-' }} - {{ $report->reportTemplate?->name ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                    {{ $report->product_name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                    {{ $report->batch_number }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-center">
                                    <a
                                        href="{{ route('arsip-laporan.show', ['reportId' => $report->id, 'folder' => $activeFolder['slug']]) }}"
                                        class="inline-flex items-center rounded-md border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700 transition-colors hover:bg-green-100"
                                    >
                                        Lihat Laporan
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-10 text-center text-sm text-gray-500">
                                    Belum ada arsip pada folder ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reports !== null)
                <div class="mt-4 border-t border-gray-100 pt-3">
                    {{ $reports->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
