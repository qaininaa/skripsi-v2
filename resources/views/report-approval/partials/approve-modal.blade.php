{{--
    Approve confirmation modal. Re-confirms the actor's username + password.

    Required:
      $action     — POST URL
      $roleLabel  — 'Supervisor' | 'Manajer'
--}}
<div
    x-cloak
    x-show="$store.approvalApproveModal.isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    @keydown.escape.window="$store.approvalApproveModal.close()"
    @click.self="$store.approvalApproveModal.close()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
>
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-base font-semibold text-gray-900">Setujui Laporan</h3>
        <p class="mt-1 text-sm text-gray-500">
            Tindakan ini akan menandatangani laporan sebagai {{ $roleLabel }}.
            Silakan masukkan kembali username dan password Anda untuk konfirmasi.
        </p>

        <form action="{{ $action }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="action" value="approve">

            <p
                x-show="$store.approvalApproveModal.authError"
                x-text="$store.approvalApproveModal.authError"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
            ></p>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                <input
                    type="text"
                    name="username"
                    autocomplete="username"
                    x-model="$store.approvalApproveModal.username"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                    required
                >
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <div class="relative">
                    <input
                        :type="$store.approvalApproveModal.showPassword ? 'text' : 'password'"
                        name="password"
                        autocomplete="current-password"
                        x-model="$store.approvalApproveModal.password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                        required
                    >
                    <button
                        type="button"
                        @click="$store.approvalApproveModal.showPassword = ! $store.approvalApproveModal.showPassword"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
                        tabindex="-1"
                    >👁</button>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button
                    type="button"
                    @click="$store.approvalApproveModal.close()"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >Batal</button>
                <button
                    type="submit"
                    class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600"
                >Setujui</button>
            </div>
        </form>
    </div>
</div>
