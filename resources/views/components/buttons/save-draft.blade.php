@props([
    'formId'      => 'report-form',
    'draftAction' => 'draft',
    'label'       => 'Simpan Draft',
])

<button
    type="button"
    @click.prevent="$store.saveConfirmModal.open({
        formId: @js($formId),
        kind: 'draft',
        title: 'Konfirmasi Simpan Draft',
        draftAction: @js($draftAction),
        submitLabel: 'Simpan',
    })"
    class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 cursor-pointer"
>
    {{ $label }}
</button>
