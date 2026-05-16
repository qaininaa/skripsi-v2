@props([
    'key'   => 'error',
    'class' => 'mb-4',
])

@if (session($key))
    <div {{ $attributes->merge(['class' => $class . ' rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700']) }}>
        {{ session($key) }}
    </div>
@endif
