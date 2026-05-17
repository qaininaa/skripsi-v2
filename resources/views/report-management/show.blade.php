@extends('layouts.app')

@section('title', 'Detail Template Laporan')
@section('page-title', 'Detail Template Laporan')

@section('content')
    <x-messages.success-message />
    <x-messages.error-message />

    {{-- Top nav --}}
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('report-templates.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <span>&#8592;</span> Kembali ke daftar
        </a>
        <div class="flex items-center gap-2">
            <x-buttons.edit :href="route('report-templates.edit', $reportTemplate)" />
            <x-buttons.delete
                :action="route('report-templates.destroy', $reportTemplate)"
                :item-name="$reportTemplate->name"
                title="Hapus Template Laporan"
                warning="Semua section dan data terkait juga akan dihapus."
            />
        </div>
    </div>

    {{-- Informasi Dasar --}}
    @include('report-management.partials.template-info', [
        'reportTemplate' => $reportTemplate,
    ])

    {{-- Section list --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        {{-- Section header --}}
        <div
            x-data="{ open: false }"
            class="border-b border-gray-100"
        >
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-800">
                    Section ({{ $reportTemplate->sections->count() }})
                </h2>
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition-colors hover:bg-indigo-100 cursor-pointer"
                >
                    + Tambah Section
                </button>
            </div>

            {{-- Inline add section form --}}
            <div x-show="open" x-cloak class="border-t border-gray-100 bg-gray-50 px-6 py-4">
                @include('report-management.partials.section-form', [
                    'action'           => route('report-templates.sections.store', $reportTemplate),
                    'method'           => 'POST',
                    'section'          => null,
                    'cancelExpression' => 'open = false',
                ])
            </div>
        </div>

        {{-- Section items --}}
        <div class="divide-y divide-gray-100">
            @forelse ($reportTemplate->sections as $index => $section)
                @include('report-management.partials.section-card', [
                    'reportTemplate'     => $reportTemplate,
                    'section'            => $section,
                    'index'              => $index,
                    'availableLocations' => $sectionAvailable[$section->id] ?? collect(),
                ])
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-400">
                    Belum ada section. Klik "+ Tambah Section" untuk memulai.
                </div>
            @endforelse
        </div>
    </div>
@endsection
