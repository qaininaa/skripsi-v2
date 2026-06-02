@props([
    'href' => '#',
    'label' => 'Lihat',
])

<a
    href="{{ $href }}"
    class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100"
>
    <span>{{ $label }}</span>
    <span aria-hidden="true">&rsaquo;</span>
</a>
