@extends('layouts.app')

@section('title', 'Edit Jenis Laporan')
@section('page-title', 'Edit Jenis Laporan')

@section('content')
    @php
        $initialMediums = old(
            'medium_templates',
            $reportTemplate->mediumTemplates->map(fn ($m) => ['name' => $m->name])->values()->toArray()
        );

        $initialIncubators = old(
            'incubator_templates',
            $reportTemplate->incubatorTemplates->map(fn ($i) => ['label' => $i->label, 'min_day' => $i->min_day])->values()->toArray()
        );
    @endphp

    <div
        x-data="reportTemplateForm({{ Js::from($initialMediums) }}, {{ Js::from($initialIncubators) }})"
        class="mx-auto max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100"
    >
        <h1 class="text-xl font-bold text-gray-900">Edit Jenis Laporan</h1>
        <p class="mt-1 text-sm text-gray-500">Perbarui konfigurasi jenis laporan.</p>

        <x-messages.success-message />
        <x-messages.error-message />
        <x-messages.validation-errors />

        <form action="{{ route('report-templates.update', $reportTemplate) }}" method="POST" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            @include('report-template-management.partials.form-fields')

            <div class="flex gap-3 pt-2">
                <a
                    href="{{ route('report-templates.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-800 cursor-pointer"
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
