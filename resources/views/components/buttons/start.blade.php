@props([
    'action'      => '#',
    'productName' => '',
    'batchNumber' => '',
    'label'       => 'Mulai',
])

<button
    type="button"
    @click.prevent="$store.startReportModal.open({
        action: @js($action),
        productName: @js($productName),
        batchNumber: @js($batchNumber),
    })"
    class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600 transition-colors hover:bg-blue-100 cursor-pointer"
>
    {{ $label }}
</button>
