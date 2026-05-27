{{--
    Shared in-progress list for supervisor & manager.
    Lists every ongoing report (not completed/archived).

    Required:
      $reports       - paginator
      $previewRoute  - route name for "Lihat" preview
      $activeStage   - current stage key
      $counts        - count map per stage key
--}}
<x-messages.success-message />
<x-messages.error-message />

@php
    $stageTabs = [
        'all'               => 'Semua',
        'pending'           => 'Belum Dikerjakan',
        'monitoring'        => 'Monitoring',
        'reading'           => 'Pembacaan',
        'review_supervisor' => 'Direview Supervisor',
        'approval_manager'  => 'Menunggu Persetujuan Manajer',
        'returned'          => 'Pengembalian Laporan',
    ];

    $statusMeta = [
        'pending' => [
            'label' => 'Belum Dikerjakan',
            'class' => 'bg-gray-100 text-gray-600',
        ],
        'in_progress_monitoring' => [
            'label' => 'Monitoring',
            'class' => 'bg-amber-100 text-amber-700',
        ],
        'in_progress_reading' => [
            'label' => 'Pembacaan',
            'class' => 'bg-purple-100 text-purple-700',
        ],
        'pending_review' => [
            'label' => 'Direview Supervisor',
            'class' => 'bg-fuchsia-100 text-fuchsia-700',
        ],
        'pending_approval' => [
            'label' => 'Menunggu Persetujuan Manajer',
            'class' => 'bg-indigo-100 text-indigo-700',
        ],
    ];
@endphp

<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="border-b border-gray-100 px-6 py-4">
        <h2 class="text-xl font-semibold text-gray-900">Laporan Sedang Dikerjakan</h2>
        <p class="mt-1 text-sm text-gray-500">Pantau laporan yang sedang dikerjakan analis pada tahap monitoring maupun pembacaan.</p>

        <div class="mt-5 flex flex-wrap items-end gap-5 border-b border-gray-200">
            @foreach ($stageTabs as $key => $label)
                @php $isActive = $activeStage === $key; @endphp
                <a
                    href="{{ request()->fullUrlWithQuery(['stage' => $key, 'page' => null]) }}"
                    @class([
                        'inline-flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-medium transition',
                        'border-emerald-500 text-emerald-700' => $isActive,
                        'border-transparent text-gray-600 hover:text-gray-800' => ! $isActive,
                    ])
                >
                    <span>{{ $label }}</span>
                    @if (($counts[$key] ?? 0) > 0)
                        <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-gray-100 px-2 text-xs text-gray-600">
                            {{ $counts[$key] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-white">
                <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-3 text-left">Tanggal</th>
                    <th class="px-6 py-3 text-left">Nama Produk</th>
                    <th class="px-6 py-3 text-left">Nomor Batch</th>
                    <th class="px-6 py-3 text-left">Tahap</th>
                    <th class="px-6 py-3 text-left">Sedang Dikerjakan Oleh</th>
                    <th class="px-6 py-3 text-left">Tim Analis</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reports as $report)
                    @php
                        $status = (string) $report->status;
                        $meta   = $statusMeta[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'bg-gray-100 text-gray-600'];

                        $supervisorApproval = $report->approvals->firstWhere('step', \Domain\Report\Models\ReportApproval::STEP_SUPERVISOR);
                        $managerApproval = $report->approvals->firstWhere('step', \Domain\Report\Models\ReportApproval::STEP_MANAGER);

                        $currentHandler = match ($status) {
                            'pending' => 'Belum ditugaskan',
                            'in_progress_monitoring', 'in_progress_reading' => $report->lockedByUser?->name ?? 'Analis',
                            'pending_review' => $supervisorApproval?->user?->name ?? 'Supervisor',
                            'pending_approval' => $managerApproval?->user?->name ?? 'Manajer',
                            default => '-',
                        };

                        $monitoringTeam = $report->analysts
                            ->where('type', 'monitoring')
                            ->pluck('user.name')
                            ->filter()
                            ->unique()
                            ->values()
                            ->implode(', ');
                        $readingTeam = $report->analysts
                            ->where('type', 'reading')
                            ->pluck('user.name')
                            ->filter()
                            ->unique()
                            ->values()
                            ->implode(', ');

                        $teamLabel = collect([
                            $monitoringTeam !== '' ? 'Monitoring: ' . $monitoringTeam : null,
                            $readingTeam !== '' ? 'Pembacaan: ' . $readingTeam : null,
                        ])->filter()->implode(' | ');
                    @endphp
                    <tr class="hover:bg-gray-50/60">
                        <td class="whitespace-nowrap px-6 py-5 text-sm text-gray-700">
                            {{ $report->created_at->translatedFormat('d M Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-5 text-sm font-medium text-gray-800">
                            {{ $report->product_name }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-5 text-sm text-gray-700">
                            {{ $report->batch_number }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-5 text-left">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $meta['class'] }}">
                                {{ $meta['label'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-5 text-sm text-gray-800">
                            {{ $currentHandler }}
                        </td>
                        <td class="px-6 py-5 text-sm text-gray-800">
                            {{ $teamLabel !== '' ? $teamLabel : '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-5">
                            <div class="flex items-center justify-center gap-2">
                                <x-buttons.preview :href="route($previewRoute, $report)" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                            Tidak ada laporan yang sedang dikerjakan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-gray-100 px-4 py-3">
        {{ $reports->links() }}
    </div>
</div>
