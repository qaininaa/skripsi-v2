@props([
    'action' => route('login.post'),
    'method' => 'POST',
    'usernameName' => 'username',
    'passwordName' => 'password',
])

@php
    $httpMethod = strtoupper($method);
    $formMethod = in_array($httpMethod, ['GET', 'POST']) ? $httpMethod : 'POST';
@endphp

<form
    action="{{ $action }}"
    method="{{ strtolower($formMethod) }}"
    class="space-y-5"
    x-data="{ isSubmitting: false, showPassword: false }"
    @submit="isSubmitting = true"
>
    @if ($formMethod !== 'GET')
        @csrf
    @endif

    @if (!in_array($httpMethod, ['GET', 'POST']))
        @method($httpMethod)
    @endif

    @if ($errors->has('session'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('session') }}
        </div>
    @endif

    <div>
        <label for="{{ $usernameName }}" class="block text-sm/6 font-medium text-gray-900">Username</label>
        <div class="mt-2">
            <input
                id="{{ $usernameName }}"
                name="{{ $usernameName }}"
                type="text"
                value="{{ old($usernameName) }}"
                autocomplete="username"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 {{ $errors->has($usernameName) ? 'outline-red-500' : 'outline-gray-300' }} placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-sky-600"
            >
            @error($usernameName)
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label for="{{ $passwordName }}" class="block text-sm/6 font-medium text-gray-900">Password</label>
        </div>
        <div class="mt-2">
            <div class="relative">
                <input
                    id="{{ $passwordName }}"
                    name="{{ $passwordName }}"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    required
                    class="block w-full rounded-md bg-white px-3 py-1.5 pr-10 text-base text-gray-900 outline-1 -outline-offset-1 {{ $errors->has($passwordName) ? 'outline-red-500' : 'outline-gray-300' }} placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-sky-600"
                >
                <x-buttons.password-toggle state="showPassword" />
            </div>
            @error($passwordName)
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <button
            type="submit"
            :disabled="isSubmitting"
            :class="isSubmitting ? 'opacity-60 cursor-wait' : ''"
            class="flex w-full justify-center rounded-md bg-sky-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-sm transition-opacity hover:bg-sky-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600"
        >
            <span x-text="isSubmitting ? 'Memeriksa...' : 'Login'"></span>
        </button>
    </div>

</form>

@if ($errors->has('login'))
    <div class="text-center text-sm text-red-700 mt-2">
        {{ $errors->first('login') }}
    </div>
@endif
