@props([
    'key'   => 'info',
    'class' => 'mb-4',
])

@if (session($key))
    <div {{ $attributes->merge(['class' => $class . ' rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700']) }}>
        {{ session($key) }}
    </div>
@endif
