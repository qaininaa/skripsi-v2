@extends('layouts.app')

@section('title', 'Detail Arsip Laporan')
@section('page-title', 'Detail Arsip Laporan')

@section('content')
    @php
        $backParams = [];
        if ($activeFolder !== null) {
            $backParams['folder'] = $activeFolder['slug'];
        } elseif (request()->query('folder')) {
            $backParams['folder'] = request()->query('folder');
        }
    @endphp

    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <a
                href="{{ route('arsip-laporan.index', $backParams) }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-700"
            >
                <span>&larr;</span>
                <span>Kembali ke Arsip</span>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Detail Arsip Laporan</h2>
                <p class="text-sm text-gray-500">
                    {{ $activeFolder['code'] ?? 'Folder Arsip' }} - Annex {{ $report->reportTemplate?->annex_number ?? '-' }}
                </p>
            </div>
        </div>
        <a
            href="{{ route('arsip-laporan.print', ['reportId' => $report->id]) }}"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700"
        >
            Cetak Laporan
        </a>
    </div>

    <x-report.readonly
        :report="$report"
        :section-instances="$sectionInstances"
        :instrument-entries="$instrumentEntries"
        :medium-entries="$mediumEntries"
        :incubators="$incubators"
        :has-swab="$hasSwab"
        :lock-map="$lockMap"
        :preview-only="true"
    />
@endsection
