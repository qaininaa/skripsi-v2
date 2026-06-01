@extends('layouts.auth')

@section('title', 'Ubah Password')
@section('auth-heading', 'Ubah Password Anda')

@section('auth-content')
    <p class="mb-6 text-sm text-gray-600">
        {{ $passwordNotice ?? 'Demi keamanan akun, Anda wajib mengganti password sebelum melanjutkan.' }}
    </p>

    <x-messages.validation-errors class="mb-4" />

    <form
        action="{{ route('password.change.update') }}"
        method="POST"
        class="space-y-4"
        x-data="{
            isSubmitting: false,
            showOldPassword: false,
            showNewPassword: false,
            showNewPasswordConfirmation: false
        }"
        @submit="isSubmitting = true"
    >
        @csrf
        @method('PUT')

        <div>
            <label for="old_password" class="mb-1 block text-sm font-medium text-gray-700">Password Lama</label>
            <div class="relative">
                <input
                    id="old_password"
                    name="old_password"
                    :type="showOldPassword ? 'text' : 'password'"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 pr-10 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100"
                >
                <x-buttons.password-toggle state="showOldPassword" />
            </div>
        </div>

        <div>
            <label for="new_password" class="mb-1 block text-sm font-medium text-gray-700">Password Baru</label>
            <div class="relative">
                <input
                    id="new_password"
                    name="new_password"
                    :type="showNewPassword ? 'text' : 'password'"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 pr-10 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100"
                >
                <x-buttons.password-toggle state="showNewPassword" />
            </div>
            <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter, mengandung angka dan simbol.</p>
        </div>

        <div>
            <label for="new_password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
            <div class="relative">
                <input
                    id="new_password_confirmation"
                    name="new_password_confirmation"
                    :type="showNewPasswordConfirmation ? 'text' : 'password'"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 pr-10 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100"
                >
                <x-buttons.password-toggle state="showNewPasswordConfirmation" />
            </div>
        </div>

        <button
            type="submit"
            :disabled="isSubmitting"
            :class="isSubmitting ? 'opacity-60 cursor-wait' : ''"
            class="w-full rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
        >
            <span x-text="isSubmitting ? 'Memeriksa...' : 'Ubah Password'"></span>
        </button>
    </form>

    <form action="{{ route('logout') }}" method="POST" class="mt-2">
        @csrf
        <button
            type="submit"
            class="w-full rounded-md border border-gray-300 bg-red-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-700 cursor-pointer"
        >
            Logout
        </button>
    </form>
@endsection
