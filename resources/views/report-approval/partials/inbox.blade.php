{{--
    Shared inbox table for supervisor & manager.

    Required:
      $reports     — paginator
      $counts      — array<string, int> for tabs (pending|approved|returned|all)
      $activeTab   — currently selected tab
      $tabRoute    — route name for tab links
      $showRoute   — route name for the "Lihat" button
--}}
@php
    $tabs = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'returned' => 'Dikembalikan',
        'all'      => 'Semua',
    ];
@endphp

<x-messages.success-message />
<x-messages.error-message />

<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
    {{-- Tabs --}}
    <div class="overflow-x-auto border-b border-gray-100">
        <nav class="flex gap-1 px-4">
            @foreach ($tabs as $key => $label)
                @php
                    $isActive = $activeTab === $key;
                    $count    = $counts[$key] ?? 0;
                @endphp
                <a
                    href="{{ route($tabRoute, ['tab' => $key]) }}"
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
                    <th class="px-6 py-3 text-left">No. Batch</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reports as $report)
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
                            <x-badges.report-status :status="$report->status" />
                        </td>
                        <td class="whitespace-nowrap px-6 py-5">
                            <div class="flex items-center justify-center gap-2">
                                <x-buttons.view :href="route($showRoute, $report)" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                            Tidak ada laporan pada tab ini.
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
