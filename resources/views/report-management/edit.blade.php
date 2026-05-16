@extends('layouts.app')

@section('title', 'Edit Tugas Pelaporan')
@section('page-title', 'Edit Tugas Pelaporan')

@section('content')
    <div class="mb-4">
        <a
            href="{{ route('report-assignment.index') }}"
            class="inline-flex items-center gap-1 text-sm text-gray-500 transition-colors hover:text-gray-700"
        >
            <span>&larr;</span>
            <span>Kembali ke daftar</span>
        </a>
    </div>

    <div class="mx-auto max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Edit Tugas Pelaporan</h1>
            <x-badges.report-status :status="$report->status" />
        </div>

        <x-messages.error-message />
        <x-messages.validation-errors />

        @if ($report->status !== 'pending')
            <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
                Laporan ini tidak dapat diubah karena statusnya bukan <strong>pending</strong>.
            </div>
        @endif

        <form
            action="{{ route('report-assignment.update', $report) }}"
            method="POST"
            class="mt-4 space-y-5 {{ $report->status !== 'pending' ? 'pointer-events-none opacity-60' : '' }}"
        >
            @csrf
            @method('PUT')

            {{-- Nama Produk --}}
            <div>
                <label for="product_name" class="mb-1 block text-sm font-medium text-gray-700">
                    Nama Produk <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="product_name"
                    name="product_name"
                    value="{{ old('product_name', $report->product_name) }}"
                    placeholder="Masukkan nama produk"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            {{-- Nomor Batch Produk --}}
            <div>
                <label for="batch_number" class="mb-1 block text-sm font-medium text-gray-700">
                    Nomor Batch Produk <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="batch_number"
                    name="batch_number"
                    value="{{ old('batch_number', $report->batch_number) }}"
                    placeholder="Masukkan nomor batch produk"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
            </div>

            {{-- Jenis Laporan --}}
            <div>
                <label for="report_template_id" class="mb-1 block text-sm font-medium text-gray-700">
                    Jenis Laporan <span class="text-red-500">*</span>
                </label>
                <select
                    id="report_template_id"
                    name="report_template_id"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
                    <option value="">- Pilih Jenis Laporan -</option>
                    @foreach ($reportTemplates as $template)
                        <option value="{{ $template->id }}" @selected(old('report_template_id', $report->report_template_id) === $template->id)>
                            Annex {{ $template->annex_number }} – {{ $template->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($report->status === 'pending')
                <div class="flex justify-end gap-3 pt-2">
                    <a
                        href="{{ route('report-assignment.index') }}"
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
            @endif
        </form>
    </div>
@endsection
