{{--
    Shared in-progress list for supervisor & manager.
    Lists every approval row with status=pending assigned to the actor.

    Required:
      $reports    — paginator
      $showRoute  — route name for the "Lihat" button
--}}
<x-messages.success-message />
<x-messages.error-message />

<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="border-b border-gray-100 px-6 py-4">
        <h2 class="text-sm font-semibold text-gray-800">Laporan yang sedang Anda kerjakan</h2>
        <p class="mt-0.5 text-xs text-gray-500">Daftar laporan yang menunggu tindakan Anda.</p>
    </div>

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
                        <td class="whitespace-nowrap px-6 py-5 text-sm font-medium text-gray-800">
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
                            Tidak ada laporan yang sedang Anda kerjakan.
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
