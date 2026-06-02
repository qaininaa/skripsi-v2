@props([
    'href'  => '#',
    'label' => 'Lanjutkan',
])

<a
    href="{{ $href }}"
    class="inline-flex items-center rounded-md border border-yellow-200 bg-yellow-50 px-3 py-1 text-xs font-semibold text-yellow-700 transition-colors hover:bg-yellow-100"
>
    {{ $label }}
</a>
