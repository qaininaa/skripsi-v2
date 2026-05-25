@extends('layouts.app')

@section('title', 'Laporan Pemantauan')
@section('page-title', 'Laporan Pemantauan')

@section('content')
    @php
        $tabs = [
            'all'                  => 'Semua',
            'belum_dikerjakan'     => 'Belum Dikerjakan',
            'sedang_dimonitoring'  => 'Sedang Dimonitoring',
            'sedang_dibaca'        => 'Sedang Dibaca',
            'dikirim'              => 'Dikirim',
            'dikembalikan'         => 'Dikembalikan',
        ];

        $statusBadge = [
            'pending'                 => ['label' => 'Belum Dikerjakan',    'class' => 'bg-blue-50 text-blue-600'],
            'in_progress_monitoring'  => ['label' => 'Sedang Dimonitoring', 'class' => 'bg-yellow-50 text-yellow-700'],
            'in_progress_reading'     => ['label' => 'Sedang Dibaca',       'class' => 'bg-amber-50 text-amber-700'],
            'pending_review'          => ['label' => 'Menunggu Review',     'class' => 'bg-purple-50 text-purple-700'],
            'pending_approval'        => ['label' => 'Menunggu Persetujuan','class' => 'bg-indigo-50 text-indigo-700'],
            'completed'               => ['label' => 'Dikirim',             'class' => 'bg-emerald-50 text-emerald-700'],
            'archived'                => ['label' => 'Diarsipkan',          'class' => 'bg-gray-100 text-gray-600'],
        ];
    @endphp

    <x-messages.success-message />
    <x-messages.error-message />

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Laporan Pemantauan</h2>
        <p class="mt-1 text-sm text-gray-500">
            Daftar semua penugasan laporan yang diberikan kepada Anda.
        </p>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        {{-- Tabs --}}
        <div class="overflow-x-auto overflow-y-hidden border-b border-gray-100 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
            <nav class="flex gap-1 px-4">
                @foreach ($tabs as $key => $label)
                    @php
                        $isActive = $activeTab === $key;
                        $count    = $counts[$key] ?? 0;
                    @endphp
                    <a
                        href="{{ route('report-fill.index', ['tab' => $key]) }}"
                        class="relative inline-flex items-center gap-2 whitespace-nowrap px-4 py-4 text-sm font-medium transition-colors
                            {{ $isActive ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"
                    >
                        <span>{{ $label }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $isActive ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $count }}
                        </span>
                        @if ($isActive)
                            <span class="absolute inset-x-2 -bottom-px h-0.5 bg-blue-500"></span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-white">
                    <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="px-6 py-3 text-left">Tanggal</th>
                        <th class="px-6 py-3 text-left">Nama Produk</th>
                        <th class="px-6 py-3 text-left">Nomor Batch Produk</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($reports as $report)
                        @php
                            $monitoringAnalyst = $report->analystOfType('monitoring')?->user;
                            $readingAnalyst    = $report->analystOfType('reading')?->user;
                            $isMine            = $monitoringAnalyst !== null && $monitoringAnalyst->id === auth()->id();
                            $badge             = $statusBadge[$report->status] ?? ['label' => $report->status, 'class' => 'bg-gray-100 text-gray-600'];
                        @endphp

                        <tr class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-6 py-5 text-sm text-gray-700">
                                {{ $report->created_at->translatedFormat('d M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-5 text-sm text-gray-700">
                                {{ $report->product_name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-5 text-sm text-gray-700">
                                {{ $report->batch_number }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-5 text-center">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                                @if ($report->lockedByUser !== null)
                                    <div class="mt-1 text-xs text-gray-400">oleh {{ $report->lockedByUser->name }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-5">
                                @php
                                    $isLocker      = $report->locked_by === auth()->id();
                                    $isUnlocked    = $report->locked_by === null;
                                    $isPending     = $report->status === 'pending';
                                    $inMonitoring  = $report->status === 'in_progress_monitoring';
                                    $inReading     = $report->status === 'in_progress_reading';
                                    $canStart      = ($isPending || (($inMonitoring || $inReading) && $isUnlocked));
                                    $canResume     = $isLocker && ($inMonitoring || $inReading);
                                @endphp
                                <div class="flex items-center justify-center gap-2">
                                    <x-buttons.view :href="route('report-fill.show', $report)" />

                                    @if ($canResume)
                                        <x-buttons.resume :href="route('report-fill.fill', $report)" />
                                    @elseif ($canStart)
                                        <x-buttons.start
                                            :action="route('report-fill.start', $report)"
                                            :product-name="$report->product_name"
                                            :batch-number="$report->batch_number"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                Tidak ada tugas pelaporan pada tab ini.
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
@endsection
