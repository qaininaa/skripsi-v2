{{--
    Admin QC: confirm duplicating a section instance, optionally with reason.
--}}
<div
    x-cloak
    x-show="$store.duplicateSectionModal.isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$store.duplicateSectionModal.close()"
    @click.self="$store.duplicateSectionModal.close()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
>
    <div
        x-show="$store.duplicateSectionModal.isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
    >
        <h3 class="text-base font-semibold text-gray-800">Duplikasi Section</h3>
        <p class="mt-1 text-xs text-gray-500">
            Section baru akan dibuat dengan lokasi yang sama, namun pengisian data dimulai dari kosong.
        </p>

        <div class="mt-3 rounded-xl bg-gray-50 px-4 py-3 text-sm">
            <span class="text-gray-500">Section</span>
            <span class="ml-2 font-semibold text-gray-800" x-text="$store.duplicateSectionModal.sectionLabel"></span>
        </div>

        <form :action="$store.duplicateSectionModal.action" method="POST" class="mt-4 space-y-4">
            @csrf
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    @click="$store.duplicateSectionModal.close()"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 cursor-pointer"
                >
                    Duplikat
                </button>
            </div>
        </form>
    </div>
</div>
