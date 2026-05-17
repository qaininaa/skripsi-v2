@extends('layouts.app')

@section('title', 'Detail Tugas Pelaporan')
@section('page-title', 'Detail Tugas Pelaporan')

@section('content')
    <x-messages.success-message />
    <x-messages.error-message />

    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('report-assignment.index') }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <span>&larr;</span><span>Kembali</span>
            </a>
            <div>
                <div class="flex items-center gap-2 text-base font-semibold text-gray-800">
                    @if ($report->reportTemplate)
                        <span class="font-bold text-gray-700">{{ $report->reportTemplate->annex_number }}</span>
                        <span class="text-gray-400">—</span>
                        <span>{{ $report->reportTemplate->name }}</span>
                    @endif
                    <x-badges.report-status :status="$report->status" />
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    {{ $report->product_name }} · Batch {{ $report->batch_number }} ·
                    {{ $report->created_at->translatedFormat('d M Y') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Section instances --}}
    <div class="space-y-3">
        @forelse ($instances as $instance)
            @php
                $section = $instance->section;
                $childCount = $instance->instanceLocations->count();
                $canDuplicate = in_array($report->status, ['pending', 'in_progress_monitoring'], true);
            @endphp
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                            {{ $instance->displayLabel() }}
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ $section?->measurement_unit ?? '—' }}</div>
                            <div class="mt-0.5 text-xs text-gray-500">
                                {{ $section?->getMeasurementTypeLabel() ?? '—' }}
                                · {{ $childCount }} lokasi
                                @if ($instance->parent_instance_id !== null)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">DUPLIKAT</span>
                                    @if ($instance->duplication_reason)
                                        <span class="ml-1 text-gray-400">— {{ $instance->duplication_reason }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($canDuplicate)
                        <button
                            type="button"
                            @click.prevent="$store.duplicateSectionModal.open({
                                action: @js(route('report-assignment.sections.duplicate', [$report, $instance])),
                                sectionLabel: @js($instance->displayLabel() . ' — ' . $section?->measurement_unit),
                            })"
                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                        >
                            + Duplikat
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-100">
                Belum ada section yang ter-bootstrap untuk laporan ini.
            </div>
        @endforelse
    </div>
@endsection
