@props([
    'action' => '#',
    'label' => 'Hapus',
    'title' => 'Hapus Item',
    'description' => null,
    'itemName' => null,
    'warning' => 'Tindakan ini tidak dapat dibatalkan.',
    'confirmLabel' => 'Ya, Hapus',
    'cancelLabel' => 'Batal',
])

@php
    $resolvedDescription = $description;

    if ($resolvedDescription === null) {
        $resolvedDescription = filled($itemName)
            ? sprintf('Yakin ingin menghapus "%s"?', $itemName)
            : 'Yakin ingin menghapus data ini?';
    }
@endphp

<button
    type="button"
    @click.prevent="$store.deleteModal.open({
        action: @js($action),
        title: @js($title),
        description: @js($resolvedDescription),
        warning: @js($warning),
        confirmLabel: @js($confirmLabel),
        cancelLabel: @js($cancelLabel),
    })"
    class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-600 transition-colors hover:bg-red-100 cursor-pointer"
>
    {{ $label }}
</button>
