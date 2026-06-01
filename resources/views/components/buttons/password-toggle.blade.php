@props([
    'state' => 'showPassword',
    'showLabel' => 'Tampilkan password',
    'hideLabel' => 'Sembunyikan password',
])

<button
    type="button"
    @click="{{ $state }} = ! {{ $state }}"
    :title="{{ $state }} ? @js($hideLabel) : @js($showLabel)"
    :aria-label="{{ $state }} ? @js($hideLabel) : @js($showLabel)"
    @class([
        'absolute inset-y-0 right-0 inline-flex items-center rounded-r-lg px-3 text-gray-400 transition-colors hover:text-gray-600',
        'focus:outline-none focus:ring-2 focus:ring-gray-200 focus:ring-offset-1',
        'cursor-pointer',
    ])
>
    <img
        x-show="!({{ $state }})"
        src="{{ asset('icons/eye.svg') }}"
        alt=""
        class="h-4 w-4"
        aria-hidden="true"
    >
    <img
        x-show="{{ $state }}"
        src="{{ asset('icons/eye-off.svg') }}"
        alt=""
        class="h-4 w-4"
        aria-hidden="true"
    >
</button>
