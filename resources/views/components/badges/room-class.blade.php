@props(['class' => null])

@php
    $colors = [
        'A' => 'bg-rose-100 text-rose-700',
        'B' => 'bg-blue-100 text-blue-700',
        'C' => 'bg-green-100 text-green-700',
        'D' => 'bg-amber-100 text-amber-700',
        'E' => 'bg-slate-100 text-slate-700',
    ];

    $colorClass = $colors[$class] ?? 'bg-gray-100 text-gray-700';
@endphp

<span class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $colorClass }}">
    {{ $class ?? '-' }}
</span>
