@props([
    'key'   => 'success',
    'class' => 'mb-4',
])

@if (session($key))
    <div {{ $attributes->merge(['class' => $class . ' rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800']) }}>
        {{ session($key) }}
    </div>
@endif
