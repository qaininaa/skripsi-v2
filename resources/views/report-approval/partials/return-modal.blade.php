{{--
    Return-to-analyst modal. Both supervisor & manager use the same shape:
    pick an analyst, write notes, confirm with username + password.

    Required:
      $action         — POST URL
      $returnTargets  — Collection<User>
      $roleLabel      — 'Supervisor' | 'Manajer'
--}}
<div
    x-cloak
    x-show="$store.approvalReturnModal.isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    @keydown.escape.window="$store.approvalReturnModal.close()"
    @click.self="$store.approvalReturnModal.close()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
>
    <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-base font-semibold text-gray-900">Kembalikan Laporan ke Analis</h3>
        <p class="mt-1 text-sm text-gray-500">
            Pilih analis tujuan dan tuliskan catatan revisi. Konfirmasi dengan
            username dan password Anda sebagai {{ $roleLabel }}.
        </p>

        <form action="{{ $action }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="action" value="return">

            <p
                x-show="$store.approvalReturnModal.authError"
                x-text="$store.approvalReturnModal.authError"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
            ></p>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Kembalikan ke</label>
                <select
                    name="returned_to_user_id"
                    x-model="$store.approvalReturnModal.returnedToUserId"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"
                    required
                >
                    <option value="">— Pilih analis —</option>
                    @foreach ($returnTargets as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->username }})</option>
                    @endforeach
                </select>
                @if ($returnTargets->isEmpty())
                    <p class="mt-1 text-xs italic text-gray-400">
                        Belum ada analis yang dapat dijadikan tujuan pengembalian.
                    </p>
                @endif
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Catatan untuk analis</label>
                <textarea
                    name="notes"
                    rows="4"
                    x-model="$store.approvalReturnModal.notes"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"
                    placeholder="cth: SP value belum diisi pada lokasi SP1, mohon dilengkapi."
                    maxlength="1000"
                ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                    <input
                        type="text"
                        name="username"
                        autocomplete="username"
                        x-model="$store.approvalReturnModal.username"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"
                        required
                    >
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <input
                            :type="$store.approvalReturnModal.showPassword ? 'text' : 'password'"
                            name="password"
                            autocomplete="current-password"
                            x-model="$store.approvalReturnModal.password"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"
                            required
                        >
                        <button
                            type="button"
                            @click="$store.approvalReturnModal.showPassword = ! $store.approvalReturnModal.showPassword"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
                            tabindex="-1"
                        >👁</button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button
                    type="button"
                    @click="$store.approvalReturnModal.close()"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >Batal</button>
                <button
                    type="submit"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600"
                >Kembalikan</button>
            </div>
        </form>
    </div>
</div>
