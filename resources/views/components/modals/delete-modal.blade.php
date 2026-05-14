<div
    x-cloak
    x-show="$store.deleteModal.isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$store.deleteModal.close()"
    @click.self="$store.deleteModal.close()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
>
    <div
        x-show="$store.deleteModal.isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl transform"
    >
        <div class="mb-3 flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100">
                <img src="{{ asset('icons/trash.svg') }}" alt="" class="h-5 w-5" aria-hidden="true">
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900" x-text="$store.deleteModal.title"></h3>
                <p class="mt-1 text-sm text-gray-500" x-text="$store.deleteModal.description"></p>
            </div>
        </div>

        <p class="mb-5 ml-15 text-sm text-gray-400" x-text="$store.deleteModal.warning"></p>

        <div class="flex justify-end gap-3">
            <button
                @click="$store.deleteModal.close()"
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 cursor-pointer"
                x-text="$store.deleteModal.cancelLabel"
            ></button>

            <form :action="$store.deleteModal.action" method="POST">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 cursor-pointer"
                    x-text="$store.deleteModal.confirmLabel"
                ></button>
            </form>
        </div>
    </div>
</div>
