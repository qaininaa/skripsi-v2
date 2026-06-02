@props([
    'formId'              => 'report-form',
    'draftAction'         => 'release',
    'finalizeAction'      => 'finalize_monitoring',
    'draftLabel'          => 'Simpan Monitoring',
    'finalizeLabel'       => 'Selesaikan Monitoring & Mulai Pembacaan',
    'draftDescription'    => '',
    'finalizeDescription' => '',
    'label'               => 'Simpan & Serahkan',
])

<button
    type="button"
    @click.prevent="
        $store.saveConfirmModal.open({
            formId: @js($formId),
            kind: 'finalize',
            title: 'Simpan & Serahkan Laporan',
            draftAction: @js($draftAction),
            finalizeAction: @js($finalizeAction),
            draftLabel: @js($draftLabel),
            finalizeLabel: @js($finalizeLabel),
            draftDescription: @js($draftDescription),
            finalizeDescription: @js($finalizeDescription),
        });
    "
    class="inline-flex items-center rounded-xl bg-sky-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sky-700 cursor-pointer"
>
    {{ $label }}
</button>
