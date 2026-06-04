<?php

namespace App\Services;

use Domain\Report\Services\MonitoringService;
use Domain\User\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\ViewErrorBag;

class AnalystReportViewService
{
    public function __construct(
        protected UserService $userService,
    ) {}

    /**
     * Prepare view-only UI data. Report-derived data must already be prepared
     * by Domain\Report\Services\ReportService.
     *
     * @return array<string, mixed>
     */
    public function prepare(array $data): array
    {
        $supervisorOptions = $this->supervisorOptions();
        $actions = $this->actions($data, $supervisorOptions);

        return array_merge($data, $actions, [
            'supervisorOptions' => $supervisorOptions,
            'modalRehydrate' => $this->modalRehydrate($data['phase'], $data, $actions),
        ]);
    }

    /**
     * @return Collection<int, array{id: string, name: string}>
     */
    private function supervisorOptions(): Collection
    {
        return $this->userService->listSupervisorOptions();
    }

    /**
     * @param  Collection<int, array{id: string, name: string}>  $supervisorOptions
     * @return array<string, mixed>
     */
    private function actions(array $data, Collection $supervisorOptions): array
    {
        $phase = $data['phase'];
        $finalizeLabel = $phase === 'reading'
            ? 'Selesaikan Pembacaan & Kirim Review'
            : 'Selesaikan Monitoring & Mulai Pembacaan';
        $finalizeDescription = $phase === 'reading'
            ? 'Data pembacaan sudah lengkap, kirim ke supervisor untuk review.'
            : 'Data monitoring sudah lengkap, lanjut ke tahap baca.';

        if ($data['isReadingRevisionSendOnlyMode']) {
            $finalizeLabel = 'Kirim ke Supervisor Langsung';
            $finalizeDescription = 'Kirim hasil revisi pembacaan langsung ke supervisor.';
        }

        return [
            'formAction' => $phase === 'reading'
                ? route('report.save-reading', $data['reportId'])
                : route('report.save-monitoring', $data['reportId']),
            'draftAction' => 'draft',
            'releaseAction' => 'release',
            'releaseLabel' => $phase === 'reading' ? 'Simpan Pembacaan' : 'Simpan Monitoring',
            'releaseDescription' => $phase === 'reading'
                ? 'Draft tersimpan, analis lain bisa melanjutkan pembacaan.'
                : 'Draft tersimpan, analis lain bisa melanjutkan monitoring.',
            'finalizeAction' => $phase === 'reading' ? 'finalize_reading' : 'finalize_monitoring',
            'finalizeLabel' => $finalizeLabel,
            'finalizeDescription' => $finalizeDescription,
            'toReadingAction' => MonitoringService::ACTION_TO_READING,
            'sendToSupervisorAction' => MonitoringService::ACTION_FINALIZE_TO_REVIEW,
            'supervisorOptions' => $supervisorOptions,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function modalRehydrate(string $phase, array $data, array $actions): ?array
    {
        $errors = session('errors', new ViewErrorBag);
        $usernameError = $errors->first('username');
        $passwordError = $errors->first('password');
        $supervisorError = $errors->first('supervisor_id');

        if ($usernameError === '' && $passwordError === '' && $supervisorError === '') {
            return null;
        }

        $oldAction = old(
            'action',
            $data['isReadingRevisionSendOnlyMode'] ? $actions['finalizeAction'] : $actions['draftAction']
        );
        $isRevisionQuickAction = in_array($oldAction, [
            $actions['toReadingAction'],
            $actions['sendToSupervisorAction'],
        ], true);
        $requiresSupervisor = ($phase === 'reading' && $oldAction === $actions['finalizeAction'])
            || $oldAction === $actions['sendToSupervisorAction'];

        $modal = [
            'kind' => 'draft',
            'title' => 'Konfirmasi Simpan Draft',
            'draftAction' => $actions['draftAction'],
            'submitLabel' => 'Simpan',
            'message' => '',
        ];

        if ($requiresSupervisor) {
            $modal = [
                'kind' => 'draft',
                'title' => 'Konfirmasi Kirim Laporan',
                'draftAction' => $oldAction,
                'submitLabel' => 'Kirim Laporan',
                'message' => 'Setelah dikirim, data tidak dapat diubah lagi. Masukkan username dan password Anda untuk melanjutkan.',
            ];
        } elseif ($phase === 'reading' && $oldAction === $actions['releaseAction']) {
            $modal = [
                'kind' => 'draft',
                'title' => 'Simpan & Serahkan Laporan',
                'draftAction' => $actions['releaseAction'],
                'submitLabel' => 'Simpan Pembacaan',
                'message' => $actions['releaseDescription'],
            ];
        } elseif ($isRevisionQuickAction) {
            $modal = [
                'kind' => 'draft',
                'title' => $oldAction === $actions['toReadingAction']
                    ? 'Konfirmasi Lanjut ke Pembacaan'
                    : 'Konfirmasi Kirim ke Supervisor',
                'draftAction' => $oldAction,
                'submitLabel' => $oldAction === $actions['toReadingAction']
                    ? 'Lanjut ke Pembacaan'
                    : 'Kirim ke Supervisor',
                'message' => '',
            ];
        } elseif (old('action') === $actions['finalizeAction'] || old('action') === $actions['releaseAction']) {
            $modal = [
                'kind' => 'finalize',
                'title' => 'Simpan & Serahkan Laporan',
                'draftAction' => $actions['releaseAction'],
                'submitLabel' => 'Simpan',
                'message' => '',
            ];
        }

        return array_merge($modal, [
            'formId' => 'report-form',
            'finalizeAction' => $actions['finalizeAction'],
            'draftLabel' => $actions['releaseLabel'],
            'finalizeLabel' => $actions['finalizeLabel'],
            'draftDescription' => $actions['releaseDescription'],
            'finalizeDescription' => $actions['finalizeDescription'],
            'username' => old('username', ''),
            'usernameError' => $usernameError,
            'passwordError' => $passwordError,
            'supervisorError' => $supervisorError,
            'selectedSupervisorId' => old('supervisor_id', ''),
            'requiresSupervisor' => $requiresSupervisor,
            'selectedAction' => $oldAction,
        ]);
    }
}
