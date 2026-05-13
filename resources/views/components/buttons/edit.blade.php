@props([
    'href' => '#',
    'label' => 'Edit',
])

<a
    href="{{ $href }}"
    class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-100"
>
    {{ $label }}
</a>
