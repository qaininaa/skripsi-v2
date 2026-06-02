<div
    x-cloak
    x-show="$store.startReportModal.isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$store.startReportModal.close()"
    @click.self="$store.startReportModal.close()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
>
    <div
        x-show="$store.startReportModal.isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
    >
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-100">
                <svg class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-800">Mulai Pengerjaan Laporan?</h3>
                <p class="mt-0.5 text-xs text-gray-500">Laporan ini akan masuk ke tahap monitoring.</p>
            </div>
        </div>

        {{-- Detail --}}
        <div class="mt-4 space-y-1 rounded-xl bg-gray-50 px-4 py-3 text-sm">
            <div class="flex gap-2">
                <span class="w-28 shrink-0 text-gray-500">Nama Produk</span>
                <span class="font-medium text-gray-800" x-text="$store.startReportModal.productName"></span>
            </div>
            <div class="flex gap-2">
                <span class="w-28 shrink-0 text-gray-500">Nomor Batch</span>
                <span class="font-medium text-gray-800" x-text="$store.startReportModal.batchNumber"></span>
            </div>
        </div>

        {{-- Description --}}
        <p class="mt-4 text-sm text-gray-600">
            Setelah dimulai, Anda akan menjadi penanggung jawab monitoring laporan ini. Analis lain hanya bisa melihat.
        </p>

        {{-- Actions --}}
        <div class="mt-6 flex justify-end gap-2">
            <button
                type="button"
                @click="$store.startReportModal.close()"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-50 cursor-pointer"
            >
                Batal
            </button>

            <form :action="$store.startReportModal.action" method="POST">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg bg-sky-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-sky-600 cursor-pointer"
                >
                    Ya, Mulai
                </button>
            </form>
        </div>
    </div>
</div>
