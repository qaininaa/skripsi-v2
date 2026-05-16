@extends('layouts.app')

@section('title', 'Pengaturan Password')
@section('page-title', 'Pengaturan Password')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Pengaturan Sistem</h2>
            <p class="mt-1 text-sm text-gray-500">Konfigurasi kebijakan keamanan akun pengguna.</p>
        </div>

        <x-messages.success-message />

        <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <div class="grid gap-4 border-b border-gray-100 p-6 md:grid-cols-[1fr_auto] md:items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Masa Berlaku Password</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Pengguna wajib mengganti password setelah jumlah hari ini. Berlaku untuk semua role kecuali super admin.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <input
                            type="number"
                            name="password_expiration_days"
                            value="{{ old('password_expiration_days', $settings['password_expiration_days']) }}"
                            min="1"
                            max="3650"
                            class="w-24 rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-bold text-gray-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                        >
                        <span class="text-md text-gray-500">hari</span>
                    </div>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-[1fr_auto] md:items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Riwayat Password yang Diingat</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Pengguna tidak bisa memakai kembali sejumlah password terakhir ini saat mengganti password.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <input
                            type="number"
                            name="password_history_count"
                            value="{{ old('password_history_count', $settings['password_history_count']) }}"
                            min="1"
                            max="50"
                            class="w-24 rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-bold text-gray-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                        >
                        <span class="text-md text-gray-500">password</span>
                    </div>
                </div>
            </div>

            <x-messages.validation-errors />

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="rounded-xl bg-green-600 px-8 py-3 text-base font-semibold text-white transition-colors hover:bg-green-700"
                >
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
@endsection
