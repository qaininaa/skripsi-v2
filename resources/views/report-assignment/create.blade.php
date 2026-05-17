@extends('layouts.app')

@section('title', 'Tambah Tugas Pelaporan')
@section('page-title', 'Tambah Tugas Pelaporan')

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
        <h1 class="text-xl font-bold text-gray-900">Tambah Tugas Pelaporan</h1>

        <x-messages.validation-errors />

        <form action="{{ route('report-assignment.store') }}" method="POST" class="mt-6 space-y-5">
            @csrf

            {{-- Nama Produk --}}
            <div>
                <label for="product_name" class="mb-1 block text-sm font-medium text-gray-700">
                    Nama Produk <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="product_name"
                    name="product_name"
                    value="{{ old('product_name') }}"
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
                    value="{{ old('batch_number') }}"
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
                        <option value="{{ $template->id }}" @selected(old('report_template_id') === $template->id)>
                            Annex {{ $template->annex_number }} – {{ $template->name }}
                        </option>
                    @endforeach
                </select>
            </div>

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
                    Simpan
                </button>
            </div>
        </form>
    </div>
@endsection
