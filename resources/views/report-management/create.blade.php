@extends('layouts.app')

@section('title', 'Tambah Template Laporan')
@section('page-title', 'Tambah Template Laporan')

@section('content')
    <div
        x-data="reportTemplateForm()"
        class="mx-auto max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100"
    >
        <h1 class="text-xl font-bold text-gray-900">Tambah Template Laporan Baru</h1>
        <p class="mt-1 text-sm text-gray-500">Buat jenis laporan Annex beserta konfigurasi medium dan inkubatornya.</p>

        <x-messages.success-message />
        <x-messages.error-message />
        <x-messages.validation-errors />

        <form action="{{ route('report-templates.store') }}" method="POST" class="mt-6 space-y-5">
            @csrf

            @include('report-management.partials.form-fields')

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
                    Simpan
                </button>
            </div>
        </form>
    </div>
@endsection
