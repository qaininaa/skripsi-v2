@extends('layouts.app')

@section('title', 'Tugas Pelaporan')
@section('page-title', 'Tugas Pelaporan')

@section('content')
    <x-messages.success-message />
    <x-messages.error-message />

    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Tugas Pelaporan</h2>
            <p class="mt-1 text-sm text-gray-500">Daftar tugas pelaporan yang dibuat.</p>
        </div>
        <a
            href="{{ route('report-assignment.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-800"
        >
            <span>+</span>
            <span>Tambah Tugas</span>
        </a>
    </div>

    <form method="GET" action="{{ route('report-assignment.index') }}" class="mb-4 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
        <div class="grid gap-3 lg:grid-cols-[1fr_180px_auto_auto]">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama atau nomor batch..."
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >

            <select
                name="status"
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
                <option value="">Semua Status</option>
                <option value="pending"     @selected(request('status') === 'pending')>Pending</option>
                <option value="in_progress" @selected(request('status') === 'in_progress')>Dikerjakan</option>
                <option value="completed"   @selected(request('status') === 'completed')>Selesai</option>
                <option value="archived"    @selected(request('status') === 'archived')>Diarsipkan</option>
            </select>

            <button
                type="submit"
                class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-800 cursor-pointer"
            >
                Cari
            </button>

            <a
                href="{{ route('report-assignment.index') }}"
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Batch Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Jenis Laporan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Dibuat Oleh</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $report->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-gray-800">
                                {{ $report->product_name }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $report->batch_number }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-700">
                                {{ $report->reportTemplate?->name ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $report->createdByUser?->name ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <x-badges.report-status :status="$report->status" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    @if ($report->status === 'pending')
                                        <x-buttons.edit :href="route('report-assignment.edit', $report)" />
                                        <x-buttons.delete
                                            :action="route('report-assignment.destroy', $report)"
                                            :item-name="$report->product_name"
                                            title="Hapus Tugas Pelaporan"
                                            warning="Tugas pelaporan yang dihapus tidak dapat dikembalikan."
                                        />
                                    @else
                                        <x-buttons.detail :href="route('report-assignment.edit', $report)" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                Data tugas pelaporan tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-4 py-3">
            {{ $reports->links() }}
        </div>
    </div>
@endsection
