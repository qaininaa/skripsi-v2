@extends('layouts.app')

@section('title', 'Template Laporan')
@section('page-title', 'Template Laporan')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Daftar Template Laporan</h2>
            <p class="mt-1 text-sm text-gray-500">Kelola template laporan Annex beserta konfigurasinya.</p>
        </div>
        <a
            href="{{ route('report-templates.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-800"
        >
            <span>+</span>
            <span>Tambah Template Laporan</span>
        </a>
    </div>

    <form method="GET" action="{{ route('report-templates.index') }}" class="mb-4 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
        <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto]">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama laporan atau kode SOP..."
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
            <button
                type="submit"
                class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-800 cursor-pointer"
            >
                Filter
            </button>
            <a
                href="{{ route('report-templates.index') }}"
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Annex</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Kode SOP</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Medium</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Inkubator</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($reportTemplates as $template)
                        <tr class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                                    Annex {{ $template->annex_number }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $template->sop_code }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800 max-w-xs truncate">{{ $template->name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-600">
                                {{ $template->medium_templates_count }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-600">
                                {{ $template->incubator_templates_count }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <x-buttons.edit :href="route('report-templates.edit', $template)" />
                                    <x-buttons.delete
                                        :action="route('report-templates.destroy', $template)"
                                        :item-name="$template->name"
                                        title="Hapus Template Laporan"
                                        warning="Semua data medium dan inkubator terkait juga akan dihapus."
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                Data template laporan tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-4 py-3">
            {{ $reportTemplates->links() }}
        </div>
    </div>
@endsection
