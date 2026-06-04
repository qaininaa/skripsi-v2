@props([
    'phase',
    'draftAction',
    'releaseAction',
    'releaseLabel',
    'releaseDescription',
    'finalizeAction',
    'finalizeLabel',
    'finalizeDescription',
    'toReadingAction',
    'sendToSupervisorAction',
    'supervisorOptions',
    'isReadingRevisionSendOnlyMode' => false,
    'isMonitoringRevisionMode' => false,
    'isDualRoleRevision' => false,
])

<div class="flex items-center gap-2">
    @if ($isReadingRevisionSendOnlyMode)
        <button
            type="button"
            @click.prevent="
                $store.saveConfirmModal.open({
                    formId: 'report-form',
                    kind: 'draft',
                    title: 'Konfirmasi Kirim Laporan',
                    draftAction: @js($finalizeAction),
                    selectedAction: @js($finalizeAction),
                    submitLabel: 'Kirim Laporan',
                    message: 'Setelah dikirim, data tidak dapat diubah lagi. Masukkan username dan password Anda untuk melanjutkan.',
                    requiresSupervisor: true,
                    supervisorOptions: @js($supervisorOptions),
                });
            "
            class="inline-flex items-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 cursor-pointer"
        >
            Kirim ke Supervisor
        </button>
    @elseif ($isMonitoringRevisionMode)
        @if ($isDualRoleRevision)
            <button
                type="button"
                @click.prevent="
                    $store.saveConfirmModal.open({
                        formId: 'report-form',
                        kind: 'draft',
                        title: 'Konfirmasi Lanjut ke Pembacaan',
                        draftAction: @js($toReadingAction),
                        selectedAction: @js($toReadingAction),
                        submitLabel: 'Lanjut ke Pembacaan',
                    });
                "
                class="inline-flex items-center rounded-xl bg-amber-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-amber-600 cursor-pointer"
            >
                Lanjut ke Pembacaan
            </button>
        @endif
        <button
            type="button"
            @click.prevent="
                $store.saveConfirmModal.open({
                    formId: 'report-form',
                    kind: 'draft',
                    title: 'Konfirmasi Kirim Laporan',
                    draftAction: @js($sendToSupervisorAction),
                    selectedAction: @js($sendToSupervisorAction),
                    submitLabel: 'Kirim Laporan',
                    message: 'Setelah dikirim, data tidak dapat diubah lagi. Masukkan username dan password Anda untuk melanjutkan.',
                    requiresSupervisor: true,
                    supervisorOptions: @js($supervisorOptions),
                });
            "
            class="inline-flex items-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 cursor-pointer"
        >
            Kirim ke Supervisor
        </button>
    @else
        <x-buttons.save-draft
            :form-id="'report-form'"
            :draft-action="$draftAction"
        />
        @if ($phase === 'reading')
            <button
                type="button"
                @click.prevent="
                    $store.saveConfirmModal.open({
                        formId: 'report-form',
                        kind: 'draft',
                        title: 'Simpan & Serahkan Laporan',
                        draftAction: @js($releaseAction),
                        selectedAction: @js($releaseAction),
                        submitLabel: 'Simpan Pembacaan',
                        message: @js($releaseDescription),
                    });
                "
                class="inline-flex items-center rounded-xl bg-sky-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sky-700 cursor-pointer"
            >
                Simpan & Serahkan
            </button>
            <button
                type="button"
                @click.prevent="
                    $store.saveConfirmModal.open({
                        formId: 'report-form',
                        kind: 'draft',
                        title: 'Konfirmasi Kirim Laporan',
                        draftAction: @js($finalizeAction),
                        selectedAction: @js($finalizeAction),
                        submitLabel: 'Kirim Laporan',
                        message: 'Setelah dikirim, data tidak dapat diubah lagi. Masukkan username dan password Anda untuk melanjutkan.',
                        requiresSupervisor: true,
                        supervisorOptions: @js($supervisorOptions),
                    });
                "
                class="inline-flex items-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 cursor-pointer"
            >
                Kirim ke Supervisor
            </button>
        @else
            <x-buttons.save-submit
                :form-id="'report-form'"
                :draft-action="$releaseAction"
                :finalize-action="$finalizeAction"
                :draft-label="$releaseLabel"
                :finalize-label="$finalizeLabel"
                :draft-description="$releaseDescription"
                :finalize-description="$finalizeDescription"
            />
        @endif
    @endif
</div>
