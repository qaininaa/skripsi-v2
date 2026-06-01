{{--
    Konfirmasi Simpan + (opsional) Simpan & Serahkan modal.

    Re-confirms the analyst's username/password before submitting the
    monitoring/reading form. Two visual variants driven by `kind`:
      - draft     → single action, simple confirmation
      - finalize  → analyst picks between "draft" and "finalize"

    All state lives on $store.saveConfirmModal.
--}}
<div
    x-cloak
    x-show="$store.saveConfirmModal.isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$store.saveConfirmModal.close()"
    @click.self="$store.saveConfirmModal.close()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
>
    <div
        x-show="$store.saveConfirmModal.isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
        @submit.prevent="$store.saveConfirmModal.submit()"
        x-data
    >
        <h3 class="text-base font-semibold text-gray-900" x-text="$store.saveConfirmModal.title"></h3>

        {{-- Variant: finalize lets analyst pick action --}}
        <template x-if="$store.saveConfirmModal.kind === 'finalize'">
            <div class="mt-4 space-y-2">
                <label
                    class="block cursor-pointer rounded-xl border p-4 transition-colors"
                    :class="$store.saveConfirmModal.selectedAction === $store.saveConfirmModal.draftAction
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300'"
                >
                    <div class="flex items-start gap-3">
                        <input
                            type="radio"
                            class="mt-1"
                            :value="$store.saveConfirmModal.draftAction"
                            x-model="$store.saveConfirmModal.selectedAction"
                        >
                        <div>
                            <div class="text-sm font-semibold text-gray-800" x-text="$store.saveConfirmModal.draftLabel"></div>
                            <div class="text-xs text-gray-500" x-text="$store.saveConfirmModal.draftDescription"></div>
                        </div>
                    </div>
                </label>
                <label
                    class="block cursor-pointer rounded-xl border p-4 transition-colors"
                    :class="$store.saveConfirmModal.selectedAction === $store.saveConfirmModal.finalizeAction
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300'"
                >
                    <div class="flex items-start gap-3">
                        <input
                            type="radio"
                            class="mt-1"
                            :value="$store.saveConfirmModal.finalizeAction"
                            x-model="$store.saveConfirmModal.selectedAction"
                        >
                        <div>
                            <div class="text-sm font-semibold text-gray-800" x-text="$store.saveConfirmModal.finalizeLabel"></div>
                            <div class="text-xs text-gray-500" x-text="$store.saveConfirmModal.finalizeDescription"></div>
                        </div>
                    </div>
                </label>
            </div>
        </template>

        {{-- Variant: draft is just a label hint --}}
        <template x-if="$store.saveConfirmModal.kind === 'draft'">
            <p class="mt-2 text-sm text-gray-500">
                Masukkan username dan password Anda untuk menyimpan.
            </p>
        </template>

        <form @submit.prevent="$store.saveConfirmModal.submit()" class="mt-4 space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                <input
                    type="text"
                    autocomplete="username"
                    x-model="$store.saveConfirmModal.username"
                    @input="$store.saveConfirmModal.clearUsernameError()"
                    :class="$store.saveConfirmModal.usernameError
                        ? 'w-full rounded-lg border border-red-300 bg-red-50/30 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100'
                        : 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100'"
                    required
                >
                <p
                    x-show="$store.saveConfirmModal.usernameError"
                    x-text="$store.saveConfirmModal.usernameError"
                    class="mt-1 text-xs text-red-600"
                ></p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <div class="relative">
                    <input
                        :type="$store.saveConfirmModal.showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        x-model="$store.saveConfirmModal.password"
                        @input="$store.saveConfirmModal.clearPasswordError()"
                        :class="$store.saveConfirmModal.passwordError
                            ? 'w-full rounded-lg border border-red-300 bg-red-50/30 px-3 py-2 pr-10 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100'
                            : 'w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100'"
                        required
                    >
                    <x-buttons.password-toggle state="$store.saveConfirmModal.showPassword" />
                </div>
                <p
                    x-show="$store.saveConfirmModal.passwordError"
                    x-text="$store.saveConfirmModal.passwordError"
                    class="mt-1 text-xs text-red-600"
                ></p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button
                    type="button"
                    @click="$store.saveConfirmModal.close()"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-600 cursor-pointer"
                    x-text="$store.saveConfirmModal.kind === 'finalize' ? 'Lanjutkan' : ($store.saveConfirmModal.submitLabel || 'Simpan')"
                ></button>
            </div>
        </form>
    </div>
</div>
